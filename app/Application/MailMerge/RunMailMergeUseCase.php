<?php

namespace App\Application\MailMerge;

use App\Application\Signatures\SendCampaignToSignatureUseCase;
use App\Application\Workflows\StartWorkflowUseCase;
use App\Domains\Documents\Models\Document;
use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\MailMerge\Models\MailMergeRecipient;
use App\Domains\Templates\Models\Template;
use App\Domains\Users\Models\User;
use App\Support\StoredFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Throwable;
use ZipArchive;

/**
 * UseCase Publipostage (mail merge)
 *
 * Génère un document par destinataire à partir d'un DOCUMENT SOURCE de type
 * `flow_type = mail_merge`.
 *
 * Version Présidence (flux simplifié) :
 *  - Le workflow est OPTIONNEL. Si aucun workflow n'est fourni, les documents
 *    personnalisés sont générés immédiatement (statut `completed`).
 *  - Si un workflow est fourni, il est démarré sur le document source et la
 *    génération n'a lieu qu'une fois le document approuvé/signé (statut `awaiting_workflow`).
 *
 * Les destinataires peuvent être fournis directement (array) ou via un
 * fichier uploadé (xls/xlsx/txt/csv) parsé par RecipientFileParser.
 *
 * Les variables globales par défaut (date_arrete, numero_arrete,
 * nom_signataire, fonction_signataire, annee...) sont fusionnées avec les
 * variables par destinataire avant remplacement dans le contenu.
 */
final class RunMailMergeUseCase
{
    /** Variables globales automatiquement pré-remplies. */
    private const AUTO_VARIABLES = [
        'annee' => 'annee',
        'date_notification' => 'date_notification',
        'date_arrete' => 'date_arrete',
        'numero_arrete' => 'numero_arrete',
        'reference' => 'reference',
        'nom_signataire' => 'nom_signataire',
        'fonction_signataire' => 'fonction_signataire',
        'ministere' => 'ministere',
        'administration' => 'administration',
        'direction' => 'direction',
    ];

    private RecipientFileParser $recipientFileParser;

    private StartWorkflowUseCase $startWorkflowUseCase;

    public function __construct(
        ?RecipientFileParser $recipientFileParser = null,
        ?StartWorkflowUseCase $startWorkflowUseCase = null,
    ) {
        $this->recipientFileParser = $recipientFileParser ?? new RecipientFileParser();
        $this->startWorkflowUseCase = $startWorkflowUseCase ?? app(StartWorkflowUseCase::class);
    }

    public function execute(array $input, User $actor): MailMergeBatch
    {
        $format = $input['format'] ?? 'pdf';

        $batch = MailMergeBatch::create([
            'document_id' => $input['document_id'] ?? null,
            'workflow_id' => $input['workflow_id'] ?? null,
            'recipients_file_path' => $input['recipients_file_path'] ?? null,
            'default_variables' => $input['default_variables'] ?? [],
            'created_by' => $actor->id,
            'title' => $input['title'] ?? $this->defaultTitle($input),
            'status' => 'processing',
            'format' => $format,
            'errors' => [],
        ]);

        $this->persistRecipientsFromInput($batch, $input);

        // Version Présidence (flux simplifié) :
        //  - Si un workflow est fourni, on le démarre sur le document source.
        //    La génération n'aura lieu qu'une fois le workflow validé (document approuvé/signé).
        //  - Sinon (workflow optionnel), on génère immédiatement les documents personnalisés.
        if (!empty($batch->workflow_id) && !empty($batch->document_id)) {
            try {
                $instance = $this->startWorkflowUseCase->execute([
                    'workflow_id' => $batch->workflow_id,
                    'document_id' => $batch->document_id,
                ], $actor);

                $batch->update([
                    'current_workflow_instance_id' => $instance->id,
                ]);
            } catch (Throwable $e) {
                $batch->update([
                    'status' => 'failed',
                    'errors' => ['Impossible de démarrer le workflow : ' . $e->getMessage()],
                    'completed_at' => now(),
                ]);

                return $batch->load(['template', 'document', 'workflow', 'creator', 'recipients']);
            }
        }

        // Si un workflow est en cours, on attend sa validation pour générer.
        if (!empty($batch->workflow_id) && !empty($batch->document_id)) {
            try {
                $this->assertWorkflowReady($batch);
            } catch (Throwable $e) {
                // Workflow non terminé → batch en attente; la génération sera
                // déclenchée par "approve" une fois le workflow terminé.
                $batch->update(['status' => 'awaiting_workflow']);

                return $batch->load(['template', 'document', 'workflow', 'currentWorkflowInstance', 'creator', 'recipients']);
            }
        }

        return $this->generate($batch, $input);
    }

    /**
     * Vérifie que le workflow du batch est terminé (document approuvé ou signé).
     * Le workflow du publipostage valide le document source : quand il est
     * approuvé/signé, on peut générer les documents personnalisés.
     */
    private function assertWorkflowReady(MailMergeBatch $batch): void
    {
        if (empty($batch->document_id)) {
            throw new \RuntimeException('Aucun document source fourni.');
        }

        $document = Document::query()->findOrFail($batch->document_id);

        if (!in_array($document->status, ['approved', 'signed'], true)) {
            throw new \RuntimeException(
                "Le workflow de validation du publipostage n'est pas terminé (statut document : {$document->status})."
            );
        }
    }

    /** Génère les documents personnalisés (après validation du workflow). */
    public function generate(MailMergeBatch $batch, array $input = []): MailMergeBatch
    {
        // Récupération de la source + du contenu de base
        try {
            $content = $this->resolveSourceContent($batch, $input);
        } catch (Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'errors' => array_merge((array) ($batch->errors ?? []), [$e->getMessage()]),
                'completed_at' => now(),
            ]);

            return $batch->load(['template', 'document', 'workflow', 'creator', 'recipients']);
        }

        // Récupération des destinataires déjà persistés à la création du batch,
        // ou repli sur un fichier / liste directe pour compatibilité rétroactive.
        $recipients = $this->loadPersistedRecipients($batch, $input);

        if (empty($recipients)) {
            $batch->update([
                'status' => 'failed',
                'errors' => array_merge((array) ($batch->errors ?? []), ['Aucun destinataire fourni (fichier vide ou liste vide).']),
                'completed_at' => now(),
            ]);

            return $batch->load(['template', 'document', 'workflow', 'creator', 'recipients']);
        }

        $batch->update(['total_recipients' => count($recipients)]);

        // Variables globales par défaut (survaleillées par celles du destinataire)
        $globalVars = array_filter((array) ($batch->default_variables ?? []), fn ($v) => $v !== null && $v !== '');
        $globalVars = array_merge($this->autoVariables(), $globalVars);

        $format = $batch->format ?? 'pdf';
        $generated = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $index => $recipientData) {
            $name = (string) ($recipientData['name'] ?? ('Destinataire ' . ($index + 1)));
            $destinataire = (string) ($recipientData['destinataire'] ?? $name);
            $variables = array_merge($globalVars, (array) ($recipientData['variables'] ?? []));

            $recipient = $batch->recipients()->where('name', $name)->first();
            if (!$recipient) {
                $recipient = MailMergeRecipient::create([
                    'batch_id' => $batch->id,
                    'name' => $name,
                    'destinataire' => $destinataire,
                    'variables' => !empty($variables) ? $variables : null,
                    'status' => 'pending',
                ]);
            } else {
                $recipient->update([
                    'destinataire' => $destinataire,
                    'variables' => !empty($variables) ? $variables : null,
                    'status' => 'pending',
                ]);
            }

            try {
                $outputPath = $this->generateOne($content, $batch, $destinataire, $variables, $format);
                $recipient->update([
                    'output_path' => $outputPath,
                    'status' => 'generated',
                    'generated_at' => now(),
                ]);
                $generated++;
            } catch (Throwable $e) {
                $recipient->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
                $failed++;
                $errors[] = "{$name} : {$e->getMessage()}";
            }
        }

        // Création du ZIP regroupant tous les documents générés (si au moins un succès)
        $zipPath = null;
        if ($generated > 0) {
            $zipPath = $this->buildZip($batch);
        }

$batch->update([
            'status' => $failed === 0 ? 'completed' : ($generated > 0 ? 'partial' : 'failed'),
            'generated_count' => $generated,
            'failed_count' => $failed,
            'zip_path' => $zipPath,
            'errors' => !empty($errors) ? $errors : null,
            'completed_at' => now(),
        ]);

// Version Présidence (flux automatisé) :
        // Dès qu'au moins un document a été généré (avec ou sans workflow),
        // la campagne est automatiquement envoyée à la signature du Directeur
        // de Cabinet pour qu'elle apparaisse dans sa boîte de validation.
        if (
            $generated > 0
            && empty($batch->submitted_for_signature_at)
        ) {
            $batch->update([
                'status' => 'pending_signature',
                'submitted_for_signature_at' => now(),
                'rejection_reason' => null,
            ]);

            try {
                app(\App\Application\Signatures\SendCampaignToSignatureUseCase::class)
                    ->notifyDirector($batch);
            } catch (Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Notification auto-campagne à signer : ' . $e->getMessage()
                );
            }
        }

        return $batch->load(['template', 'document', 'workflow', 'currentWorkflowInstance', 'creator', 'recipients']);
    }

    /** Résout le contenu texte de base selon la source choisie. */
    private function resolveSourceContent(MailMergeBatch $batch, array $input): string
    {
        // 1) Document source (draft, approuvé ou signé — génération immédiate possible)
        if (!empty($batch->document_id)) {
            $document = Document::query()->findOrFail($batch->document_id);

            // Contenu texte du document
            $content = (string) $document->content;

            // Repli : extraire le texte du fichier source .docx si disponible
            if (trim($content) === '' && $document->source_file_path && Storage::disk('public')->exists($document->source_file_path)) {
                $content = StoredFile::withLocal($document->source_file_path, fn (string $local) => $this->extractDocxText($local));
            }

            if (trim($content) === '') {
                throw new \RuntimeException('Le document sélectionné ne contient aucun contenu exploitable.');
            }

            // Pré-remplissage automatique depuis le document
            $this->autoFillFromDocument($batch, $document);

            return $content;
        }

        // 2) Fichier source uploadé
        if (!empty($batch->source_file_path) && Storage::disk('public')->exists($batch->source_file_path)) {
            $ext = strtolower(pathinfo($batch->source_file_path, PATHINFO_EXTENSION));

            return StoredFile::withLocal($batch->source_file_path, fn (string $local) => match ($ext) {
                'docx' => $this->extractDocxText($local),
                'pdf' => $this->extractPdfText($local),
                default => (string) Storage::disk('public')->get($batch->source_file_path),
            });
        }

        // 3) Template pré-enregistré (rétro-compatibilité)
        if (!empty($batch->template_id)) {
            $template = Template::query()->findOrFail($batch->template_id);
            if (!empty($template->content)) {
                return (string) $template->content;
            }
            if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                return (string) Storage::disk('public')->get($template->file_path);
            }
        }

        throw new \RuntimeException('Aucune source de contenu valide (document, fichier ou template).');
    }

    /** Persiste les destinataires dès la création du batch pour permettre la génération post-workflow. */
    private function persistRecipientsFromInput(MailMergeBatch $batch, array $input): void
    {
        $recipients = $this->resolveRecipients($batch, $input);
        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $recipientData) {
            $name = (string) ($recipientData['name'] ?? 'Destinataire');
            $destinataire = (string) ($recipientData['destinataire'] ?? $name);
            $variables = (array) ($recipientData['variables'] ?? []);

            MailMergeRecipient::query()->firstOrCreate(
                [
                    'batch_id' => $batch->id,
                    'name' => $name,
                ],
                [
                    'destinataire' => $destinataire,
                    'variables' => !empty($variables) ? $variables : null,
                    'status' => 'pending',
                ]
            );
        }
    }

    /** Charge les destinataires déjà persistés ou reconstitue une liste transitoire depuis le fichier / input. */
    private function loadPersistedRecipients(MailMergeBatch $batch, array $input): array
    {
        $existing = $batch->recipients()->orderBy('created_at')->get();
        if ($existing->isNotEmpty()) {
            return $existing->map(fn (MailMergeRecipient $recipient) => [
                'name' => $recipient->name,
                'destinataire' => $recipient->destinataire ?: $recipient->name,
                'variables' => (array) ($recipient->variables ?? []),
            ])->all();
        }

        return $this->resolveRecipients($batch, $input);
    }

    /** Résout la liste des destinataires (fichier parsé ou liste directe). */
    private function resolveRecipients(MailMergeBatch $batch, array $input): array
    {
        if (!empty($batch->recipients_file_path) && Storage::disk('public')->exists($batch->recipients_file_path)) {
            $originalName = basename($batch->recipients_file_path);

            return StoredFile::withLocal(
                $batch->recipients_file_path,
                fn (string $local) => $this->recipientFileParser->parse($local, $originalName)
            );
        }

        return $input['recipients'] ?? [];
    }

    /** Pré-remplit les variables globales par défaut depuis un document. */
    private function autoFillFromDocument(MailMergeBatch $batch, Document $document): void
    {
        $defaults = (array) ($batch->default_variables ?? []);

        $fills = [
            'reference' => $defaults['reference'] ?? $document->reference,
            'numero_arrete' => $defaults['numero_arrete'] ?? $document->document_number,
            'date_arrete' => $defaults['date_arrete'] ?? $document->document_date?->format('d/m/Y'),
        ];

        foreach ($fills as $key => $value) {
            if (!empty($value)) {
                $defaults[$key] = (string) $value;
            }
        }

        $batch->update(['default_variables' => $defaults]);
    }

    /** Variables globales automatiques (dates, année, etc.). */
    private function autoVariables(): array
    {
        $now = now();

        return [
            'annee' => (string) $now->year,
            'date_notification' => $now->format('d/m/Y'),
        ];
    }

    /** Génère un document pour un destinataire donné. */
    private function generateOne(string $content, MailMergeBatch $batch, string $name, array $variables, string $format): string
    {
        if (trim($content) === '') {
            throw new \RuntimeException('Le contenu source est vide.');
        }

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $content);
        }

        $dir = 'mailmerge/' . $batch->id . '/' . $this->slugify($name);
        $slug = $this->slugify($name) ?: 'destinataire';
        $filename = $slug . '-' . uniqid() . '.' . $format;
        $path = $dir . '/' . $filename;
        $disk = Storage::disk('public');

        if ($format === 'pdf') {
            $html = nl2br(e($content));
            $pdf = Pdf::loadHTML($html);
            $disk->put($path, $pdf->output());
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
                $section->addText($line);
            }
            $tmp = tempnam(sys_get_temp_dir(), 'afmm');
            $phpWord->save($tmp, 'Word2007');
            $disk->put($path, file_get_contents($tmp));
            @unlink($tmp);
        } else {
            $disk->put($path, $content);
        }

        return $path;
    }

    /** Extrait le texte d'un fichier DOCX (via ZIP + XML). */
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

            $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            foreach ($doc->getElementsByTagNameNS($ns, 'p') as $p) {
                $texts = [];
                foreach ($p->getElementsByTagNameNS($ns, 't') as $t) {
                    $texts[] = $t->textContent;
                }
                $paragraphs[] = implode('', $texts);
            }

            return implode("\n", $paragraphs);
        } finally {
            $zip->close();
        }
    }

    /** Extrait le texte d'un PDF (best effort, sans librairie externe). */
    private function extractPdfText(string $fullPath): string
    {
        $content = file_get_contents($fullPath);
        if ($content === false) {
            return '';
        }

        // Extraction des flux texte entre BT et ET (approche simplifiée)
        preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches);

        return implode(' ', $matches[1] ?? []);
    }

    /** Construit un ZIP avec tous les documents générés du batch. */
    private function buildZip(MailMergeBatch $batch): ?string
    {
        $disk = Storage::disk('public');
        $zipTmp = tempnam(sys_get_temp_dir(), 'afzip');
        $zip = new ZipArchive();

        if ($zip->open($zipTmp, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Impossible de créer l'archive ZIP.");
        }

        $added = 0;
        foreach ($batch->recipients as $recipient) {
            if ($recipient->status === 'generated' && $recipient->output_path && $disk->exists($recipient->output_path)) {
                $basename = basename($recipient->output_path);
                $zipName = $this->slugify((string) ($recipient->destinataire ?: $recipient->name)) ?: 'publipostage';
                $zip->addFromString(
                    $zipName . '-' . $basename,
                    (string) $disk->get($recipient->output_path)
                );
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipTmp);

            return null;
        }

        $zipPath = 'mailmerge/zip-' . $batch->id . '-' . uniqid() . '.zip';
        $disk->put($zipPath, (string) file_get_contents($zipTmp));
        @unlink($zipTmp);

        return $zipPath;
    }

    private function defaultTitle(array $input): string
    {
        if (!empty($input['title'])) {
            return (string) $input['title'];
        }

        if (!empty($input['document_id'])) {
            $doc = Document::query()->find($input['document_id']);

            return $doc ? ('Publipostage – ' . $doc->subject) : 'Publipostage';
        }

        if (!empty($input['template_id'])) {
            $template = Template::query()->find($input['template_id']);

            return $template ? ('Publipostage – ' . $template->name) : 'Publipostage';
        }

        return 'Publipostage';
    }

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?? $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim((string) $value, '-');
    }
}
