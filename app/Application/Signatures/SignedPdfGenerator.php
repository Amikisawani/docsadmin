<?php

namespace App\Application\Signatures;

use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;

/**
 * Génère le PDF final « verrouillé » d'un document signé.
 *
 * Rendu signature haute qualité :
 *  - corps du document (contenu texte ou fichier Word source .docx)
 *  - signature posée EN OVERLAY directement sur le document, à l'emplacement
 *    (en %) choisi par le Directeur de Cabinet, sans ajouter de page et sans
 *    casser la mise en page.
 *  - Aucune mention parasite : le PDF n'affiche QUE la signature (image ou
 *    tracé vectoriel) + le nom et la fonction du signataire. Toutes les
 *    informations techniques (identité, date, IP, navigateur, hash, etc.)
 *    sont enregistrées uniquement dans les journaux d'audit.
 */
final class SignedPdfGenerator
{
    public function generate(Document $document, Signature $signature, User $signer, ?array $position = null): string
    {
        $bodyHtml = $this->buildBodyHtml($document);
        $signatureOverlay = $this->buildSignatureOverlay($signature, $signer, $position);

        // Position par défaut : en bas à droite du document (si non précisée).
        $posX = $position['x'] ?? 68;
        $posY = $position['y'] ?? 82;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 42px 48px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; }

    .document-page {
        position: relative;
        width: 100%;
    }

    .header {
        border-bottom: 2px solid #0f172a;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .header h1 { font-size: 16px; margin: 0; }
    .header .meta { font-size: 11px; color: #64748b; margin-top: 4px; }

    .body { margin-bottom: 42px; }
    .body p { margin: 0 0 10px; text-align: justify; }

    /* Signature posée sur le document (overlay) */
    .signature-overlay {
        position: absolute;
        left: {$posX}%;
        top: {$posY}%;
        transform: translate(-50%, -50%);
        z-index: 50;
        text-align: center;
        line-height: 1.1;
    }
    .signature-overlay .sig-img {
        max-height: 80px;
        max-width: 260px;
        margin: 0 auto;
        display: block;
    }
</style>
</head>
<body>
    <div class="document-page">
        <div class="header">
            <h1>{$this->e($document->subject)}</h1>
            <div class="meta">N° {$this->e($document->document_number)} — {$this->e($document->document_type)} — {$this->e($document->document_date?->format('d/m/Y') ?? '')}</div>
        </div>

        <div class="body">
            {$bodyHtml}
        </div>

        {$signatureOverlay}
    </div>
</body>
</html>
HTML;

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4');

        $fileName = 'signed/'.$document->id.'-'.now()->format('Ymd-His').'.pdf';

        try {
            Storage::disk('public')->put($fileName, $pdf->output());
        } catch (\Throwable $e) {
            Log::warning('Échec stockage PDF signé : '.$e->getMessage());
            throw $e;
        }

        return $fileName;
    }

    private function buildBodyHtml(Document $document): string
    {
        // 1) Si un fichier Word source (.docx) est présent, tenter d'extraire le texte.
        if ($document->source_file_path) {
            $full = storage_path('app/public/'.$document->source_file_path);
            if (is_file($full)) {
                $extracted = $this->extractDocxText($full);
                if ($extracted !== null && trim($extracted) !== '') {
                    return $extracted;
                }
            }
        }

        // 2) Sinon, utiliser le contenu texte du document.
        if ($document->content && trim($document->content) !== '') {
            return nl2br($this->e($document->content));
        }

        // 3) Dernier recours.
        return '<p>Aucun contenu disponible.</p>';
    }

    private function extractDocxText(string $fullPath): ?string
    {
        try {
            $phpWord = IOFactory::load($fullPath);
            $parts = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = null;
                    if (method_exists($element, 'getText')) {
                        $text = $element->getText();
                    } elseif (method_exists($element, 'getTextElements')) {
                        $text = '';
                        foreach ($element->getTextElements() as $run) {
                            if (method_exists($run, 'getText')) {
                                $text .= $run->getText();
                            }
                        }
                    }

                    if ($text !== null && trim((string) $text) !== '') {
                        $parts[] = '<p>'.$this->e((string) $text).'</p>';
                    }
                }
            }

            return $parts === [] ? null : implode("\n", $parts);
        } catch (\Throwable $e) {
            Log::info('Extraction DOCX impossible : '.$e->getMessage());

            return null;
        }
    }

    /**
     * Construit l'overlay de signature (image seule).
     *
     * Version Présidence : le PDF n'affiche QUE la signature.
     * Aucune mention parasite (nom, fonction, date, etc.) n'est ajoutée
     * visuellement sur le document. Toutes les informations techniques
     * (identité, date, heure, IP, navigateur, appareil, hash, etc.)
     * sont enregistrées uniquement dans les journaux d'audit.
     */
    private function buildSignatureOverlay(Signature $signature, User $signer, ?array $position): string
    {
        $image = $this->loadSignatureImage($signature);

        $visual = $image !== null
            ? '<img class="sig-img" src="'.$image.'" alt="Signature">'
            : '<div class="sig-name" style="font-family: \'DejaVu Sans\', cursive; font-style: italic; font-size: 22px;">'.$this->e($signer->name).'</div>';

        return <<<HTML
<div class="signature-overlay">
    {$visual}
</div>
HTML;
    }

    private function loadSignatureImage(Signature $signature): ?string
    {
        if (! $signature->image_path) {
            return null;
        }

        $full = storage_path('app/public/'.$signature->image_path);
        if (! is_file($full)) {
            return null;
        }

        $mime = mime_content_type($full) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($full));
    }

    private function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
