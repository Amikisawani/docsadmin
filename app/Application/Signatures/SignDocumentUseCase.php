<?php

namespace App\Application\Signatures;

use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\DocumentSignature;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use App\Events\DocumentSigned;
use Illuminate\Support\Arr;

final class SignDocumentUseCase
{
    public function __construct(
        private readonly SignedPdfGenerator $signedPdfGenerator,
    ) {}

    private function parseBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        if (str_contains($userAgent, 'Edg/')) return 'Microsoft Edge';
        if (str_contains($userAgent, 'Chrome/')) return 'Google Chrome';
        if (str_contains($userAgent, 'Firefox/')) return 'Mozilla Firefox';
        if (str_contains($userAgent, 'Safari/')) return 'Apple Safari';
        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/')) return 'Internet Explorer';
        return 'Inconnu';
    }

    private function parseDevice(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) return 'Mobile';
        if (str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad')) return 'Tablette';
        return 'Bureau';
    }

    public function execute(array $input, User $actor): DocumentSignature
    {
        $documentId = (string)($input['document_id'] ?? '');
        $signatureId = (string)($input['signature_id'] ?? '');
        $position = $input['position'] ?? null;
        $isMailMerge = (bool)($input['is_mail_merge'] ?? false);
        $pages = max(1, (int)($input['pages'] ?? 1));

        // Guard: seuls les rôles hiérarchiques autorisés peuvent signer
        abort_unless(
            $actor->canSignDocuments(),
            403,
            'Cette action est réservée aux rôles : ' . implode(', ', User::signingRoles())
        );

        $document = Document::query()->findOrFail($documentId);
        $signature = Signature::query()->findOrFail($signatureId);

        // Guard: signature belongs to actor
        abort_unless((string)$signature->user_id === (string)$actor->id, 403, 'Cette signature ne vous appartient pas.');

        // Guard: idempotence - prevent duplicate signature record by (document, signature)
        $existing = DocumentSignature::query()
            ->where('document_id', $document->id)
            ->where('signature_id', $signature->id)
            ->first();

        if ($existing) {
            return $existing->load(['document', 'signature', 'signer']);
        }

        // Guard: document must have been submitted for signature
        abort_unless(
            $document->submitted_for_signature_at !== null && $document->status === 'pending',
            409,
            'Ce document n\'est pas en attente de signature.'
        );

        // Guard: document must not be deleted
        abort_unless((bool)$document->is_deleted === false, 409, 'Document supprimé.');

        // Generate cryptographic hash of the document
        $hashSignature = hash_hmac(
            'sha256',
            (string)$document->hash,
            $actor->id . now()->toIso8601String()
        );

        $docSignature = DocumentSignature::create([
            'document_id' => $document->id,
            'signature_id' => $signature->id,
            'signed_by' => $actor->id,
            'type' => $signature->type,
            'hash_signature' => $hashSignature,
            'signed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'position' => $position,
        ]);

        // Audit (journal d'audit complet — Version Présidence)
        $historyData = [
            'user_id' => $actor->id,
            'action' => 'signed',
            'description' => 'Document signé - ' . $signature->type,
            'metadata' => [
                'signature_type' => $signature->type,
                'signature_id' => $signature->id,
                'signature_label' => $signature->label,
                'position' => $position,
                'is_mail_merge' => $isMailMerge,
                'pages' => $pages,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent() ?: null,
                'browser' => $this->parseBrowser(request()->userAgent()),
                'device' => $this->parseDevice(request()->userAgent()),
                'hash_signature' => $hashSignature,
                'document_hash' => $document->hash,
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'signer_id' => $actor->id,
                'signer_name' => $actor->name,
                'signer_email' => $actor->email,
                'signer_role' => $actor->roles->pluck('name')->first(),
                'action_at' => now()->toIso8601String(),
                'unique_id' => $docSignature->id,
                'version' => '2.0',
            ],
        ];
        $document->histories()->create($historyData);

        // Génération du PDF final signé (verrouillé) — Phase D
        try {
            $signedPdfPath = $this->signedPdfGenerator->generate($document, $signature, $actor, $position);
        } catch (\Throwable $e) {
            $signedPdfPath = null;
            \Illuminate\Support\Facades\Log::warning('PDF signé non généré : ' . $e->getMessage());
        }

// Update document status
        $document->update([
            'status' => 'signed',
            'signed_pdf_path' => $signedPdfPath,
        ]);

        // Diffusion temps réel (WebSocket) à l'auteur du document
        try {
            broadcast(new DocumentSigned($document, $actor));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast DocumentSigned : ' . $e->getMessage());
        }

        return $docSignature->load(['document', 'signature', 'signer']);
    }
}

