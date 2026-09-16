<?php

namespace App\Application\Signatures;

use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\MailMerge\Models\MailMergeRecipient;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use App\Support\StoredFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Signe une campagne de publipostage : le Directeur de Cabinet signe une seule
 * fois, et sa signature (image + nom + fonction) est recopiée sur chaque PDF
 * de la campagne.
 *
 * Toutes les informations techniques (identité, date, IP, navigateur, hash)
 * sont enregistrées uniquement dans les journaux d'audit — jamais sur le PDF.
 */
final class SignMailMergeCampaignUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $input, User $actor): MailMergeBatch
    {
        $batch = MailMergeBatch::query()->with('document', 'creator', 'recipients')->findOrFail($input['batch_id']);

        // Guard : seul le Directeur de Cabinet (détenteur du pouvoir de signature) peut signer
        abort_unless(
            $actor->canSignDocuments(),
            403,
            'Seul le Directeur de Cabinet peut signer une campagne.'
        );

        // Guard : la signature appartient au signataire
        $signature = Signature::query()->findOrFail($input['signature_id']);
        abort_unless(
            (string) $signature->user_id === (string) $actor->id,
            403,
            'Cette signature ne vous appartient pas.'
        );

        // Guard : campagne en attente de signature
        abort_unless(
            $batch->status === 'pending_signature',
            409,
            'Cette campagne n\'est pas en attente de signature.'
        );

        // Guard : pas déjà signée
        abort_unless(
            empty($batch->signed_at),
            409,
            'Cette campagne a déjà été signée.'
        );

        $position = $input['position'] ?? ['x' => 68, 'y' => 82];

        $actor->loadMissing('roles');
        $signedZipPath = $this->signAllRecipients($batch, $signature, $actor, $position);

        $batch->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by' => $actor->id,
            'signature_id' => $signature->id,
            'signed_zip_path' => $signedZipPath,
        ]);

        // Notification au créateur de la campagne
        $creator = $batch->creator;
        if ($creator) {
            $this->notificationService->notify(
                $creator,
                'Campagne signée',
                'La campagne « ' . $batch->title . ' » a été signée par le Directeur de Cabinet.',
                'success',
                [
                    'batch_id' => $batch->id,
                    'title' => $batch->title,
                    'action_url' => '/mail-merge',
                ]
            );
        }

        // Audit avec toutes les infos techniques (jamais sur le PDF)
        activity()
            ->causedBy($actor)
            ->performedOn($batch)
            ->withProperties([
                'signed_by' => $actor->name,
                'signed_at' => now()->toIso8601String(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'signature_id' => $signature->id,
                'recipients_count' => $batch->total_recipients,
                'hash' => $this->campaignHash($batch),
            ])
            ->log('campaign_signed');

        return $batch->fresh()->load('document', 'creator', 'recipients');
    }

    /**
     * Applique la signature sur chaque PDF généré de la campagne et regroupe
     * le tout dans un ZIP final signé.
     */
    private function signAllRecipients(MailMergeBatch $batch, Signature $signature, User $actor, array $position): ?string
    {
        $disk = Storage::disk('public');
        $recipients = $batch->recipients()->get();

        if ($recipients->isEmpty()) {
            abort(422, 'Aucun destinataire à signer dans cette campagne.');
        }

        $signedDir = 'mailmerge/' . $batch->id . '/signed';
        $disk->makeDirectory($signedDir);

        $signedCount = 0;
        $lastError = null;

        foreach ($recipients as $recipient) {
            try {
                $signedPdf = $this->signRecipientPdf($batch, $recipient, $signature, $actor, $position);

                $basename = $recipient->output_path
                    ? pathinfo($recipient->output_path, PATHINFO_FILENAME)
                    : preg_replace('/[^a-z0-9]+/i', '-', (string) ($recipient->destinataire ?: $recipient->name));
                $signedPath = $signedDir . '/' . trim((string) $basename, '-') . '.pdf';
                $disk->put($signedPath, $signedPdf);

                $recipient->update([
                    'output_path' => $signedPath,
                    'status' => 'signed',
                ]);

                $signedCount++;
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                \Illuminate\Support\Facades\Log::warning(
                    'Signature campagne - échec destinataire ' . $recipient->name . ' : ' . $e->getMessage()
                );
            }
        }

        if ($signedCount === 0) {
            abort(422, $lastError ?: 'Impossible de signer les documents de cette campagne.');
        }

        return $this->buildSignedZip($batch, $signedDir);
    }

    /**
     * Signe un PDF de destinataire en le régénérant via DomPDF avec la signature
     * posée en overlay (image + nom + fonction), sans aucune mention parasite.
     */
    private function signRecipientPdf(
        MailMergeBatch $batch,
        MailMergeRecipient $recipient,
        Signature $signature,
        User $actor,
        array $position
    ): string {
        // Récupérer le contenu personnalisé du destinataire (après fusion des variables)
        $content = $this->resolveRecipientContent($batch, $recipient);

        $posX = $position['x'] ?? 68;
        $posY = $position['y'] ?? 82;

        $signatureImage = $this->loadSignatureImage($signature);
        $name = htmlspecialchars($actor->name, ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars(
            $actor->job_title ?? ($actor->roles->pluck('name')->implode(', ') ?: ''),
            ENT_QUOTES,
            'UTF-8'
        );
        $date = now()->format('d/m/Y');

        $visual = $signatureImage !== null
            ? '<img class="sig-img" src="' . $signatureImage . '" alt="Signature">'
            : '<div class="sig-cursive">' . $name . '</div>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 42px 48px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; }
    .document-page { position: relative; width: 100%; }
    .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
    .header h1 { font-size: 16px; margin: 0; }
    .header .meta { font-size: 11px; color: #64748b; margin-top: 4px; }
    .body { margin-bottom: 42px; }
    .body p { margin: 0 0 10px; text-align: justify; }
    .signature-overlay {
        position: absolute; left: {$posX}%; top: {$posY}%;
        transform: translate(-50%, -50%); z-index: 50; text-align: center;
        max-width: 46%;
    }
    .signature-overlay .sig-img { max-height: 64px; max-width: 220px; margin: 0 auto 4px; display: block; }
    .signature-overlay .sig-name { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .signature-overlay .sig-role { font-size: 10px; color: #475569; line-height: 1.2; }
    .signature-overlay .sig-date { font-size: 9px; color: #64748b; margin-top: 2px; }
    .signature-overlay .sig-cursive { font-family: 'DejaVu Sans', cursive; font-style: italic; font-size: 22px; color: #0f172a; }
</style>
</head>
<body>
    <div class="document-page">
        <div class="header">
            <h1>{$this->e($batch->title ?? $batch->document?->subject ?? 'Document')}</h1>
            <div class="meta">{$this->e($recipient->destinataire ?: $recipient->name)}</div>
        </div>
        <div class="body">{$content}</div>
        <div class="signature-overlay">
            {$visual}
            <div class="sig-name">{$name}</div>
            <div class="sig-role">{$role}</div>
            <div class="sig-date">{$date}</div>
        </div>
    </div>
</body>
</html>
HTML;

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4');

        return $pdf->output();
    }

    /**
     * Reconstruit le contenu personnalisé d'un destinataire en relisant le
     * document source et en appliquant les variables de fusion.
     */
    private function resolveRecipientContent(MailMergeBatch $batch, MailMergeRecipient $recipient): string
    {
        $disk = Storage::disk('public');

        // 1) Document déjà fusionné (txt/html) : on le reprend tel quel.
        if ($recipient->output_path && $disk->exists($recipient->output_path)) {
            $ext = strtolower((string) pathinfo($recipient->output_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['txt', 'text'], true)) {
                $raw = (string) $disk->get($recipient->output_path);
                if (trim($raw) !== '') {
                    return nl2br($this->e($raw));
                }
            }
            if (in_array($ext, ['html', 'htm'], true)) {
                $raw = (string) $disk->get($recipient->output_path);
                if (trim($raw) !== '') {
                    return $raw;
                }
            }
            if ($ext === 'pdf') {
                $extracted = StoredFile::withLocal(
                    $recipient->output_path,
                    fn (string $local) => $this->extractPdfText($local)
                );
                if (trim($extracted) !== '') {
                    return nl2br($this->e($extracted));
                }
            }
            if (in_array($ext, ['docx', 'doc'], true)) {
                $extracted = StoredFile::withLocal(
                    $recipient->output_path,
                    fn (string $local) => $this->extractDocxText($local)
                );
                if (trim($extracted) !== '') {
                    return nl2br($this->e($extracted));
                }
            }
        }

        // 2) Reconstruire depuis le modèle (contenu, .txt/.pdf/.docx, fichier de campagne).
        $content = $this->resolveSourceTemplate($batch);

        if (trim($content) === '') {
            throw new \RuntimeException(
                'Impossible de retrouver le contenu du document pour '.$recipient->name.'. Vérifiez le modèle source (texte, .txt ou .pdf).'
            );
        }

        $vars = array_merge(
            (array) ($batch->default_variables ?? []),
            (array) ($recipient->variables ?? [])
        );

        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $content);
        }

        return nl2br($this->e($content));
    }

    private function resolveSourceTemplate(MailMergeBatch $batch): string
    {
        $document = $batch->document;
        $content = (string) ($document?->content ?? '');

        if (trim($content) === '' && $document?->source_file_path && Storage::disk('public')->exists($document->source_file_path)) {
            $content = StoredFile::withLocal(
                $document->source_file_path,
                fn (string $local) => $this->extractSourceFileText($local, $document->source_file_path)
            );
        }

        if (trim($content) === '' && $batch->source_file_path && Storage::disk('public')->exists($batch->source_file_path)) {
            $content = StoredFile::withLocal(
                $batch->source_file_path,
                fn (string $local) => $this->extractSourceFileText($local, $batch->source_file_path)
            );
        }

        return $content;
    }

    private function extractSourceFileText(string $localPath, string $storedPath): string
    {
        $ext = strtolower((string) pathinfo($storedPath, PATHINFO_EXTENSION));

        return match ($ext) {
            'docx', 'doc' => $this->extractDocxText($localPath),
            'pdf' => $this->extractPdfText($localPath),
            'html', 'htm' => trim(html_entity_decode(strip_tags((string) file_get_contents($localPath)), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'txt', 'text' => (string) file_get_contents($localPath),
            default => $this->extractDocxText($localPath) ?: $this->extractPdfText($localPath) ?: (string) file_get_contents($localPath),
        };
    }

    private function extractPdfText(string $fullPath): string
    {
        $content = file_get_contents($fullPath);
        if ($content === false) {
            return '';
        }

        preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches);

        return implode(' ', $matches[1] ?? []);
    }

    private function loadSignatureImage(Signature $signature): ?string
    {
        if (!$signature->image_path) {
            return null;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($signature->image_path)) {
            return null;
        }

        $bytes = $disk->get($signature->image_path);
        $mime = $disk->mimeType($signature->image_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) $bytes);
    }

    private function buildSignedZip(MailMergeBatch $batch, string $signedDir): ?string
    {
        $disk = Storage::disk('public');
        $zipTmp = tempnam(sys_get_temp_dir(), 'afsig');
        $zip = new ZipArchive();

        if ($zip->open($zipTmp, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Impossible de créer l'archive ZIP signée.");
        }

$added = 0;
        foreach ($batch->recipients()->where('status', 'signed')->get() as $recipient) {
            if ($recipient->output_path && $disk->exists($recipient->output_path)) {
                $basename = basename($recipient->output_path);
                $zip->addFromString($basename, (string) $disk->get($recipient->output_path));
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipTmp);
            return null;
        }

        $signedZipPath = 'mailmerge/' . $batch->id . '/signed-' . now()->format('Ymd-His') . '.zip';
        $disk->put($signedZipPath, (string) file_get_contents($zipTmp));
        @unlink($zipTmp);

        return $signedZipPath;
    }

    private function campaignHash(MailMergeBatch $batch): string
    {
        $material = $batch->id . '|' . $batch->title . '|' . $batch->total_recipients . '|' . $batch->signed_at?->toIso8601String();
        return hash('sha256', $material);
    }

    private function extractDocxText(string $fullPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return '';
        }

        try {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml === false) {
                return '';
            }

            $doc = new \DOMDocument();
            @$doc->loadXML($xml);
            $paragraphs = [];

            foreach ($doc->getElementsByTagName('w:p') as $p) {
                $texts = [];
                foreach ($p->getElementsByTagName('w:t') as $t) {
                    $texts[] = $t->textContent;
                }
                $paragraphs[] = implode('', $texts);
            }

            return implode("\n", $paragraphs);
        } finally {
            $zip->close();
        }
    }

    private function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
