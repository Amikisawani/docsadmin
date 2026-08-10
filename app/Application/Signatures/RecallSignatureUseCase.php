<?php

namespace App\Application\Signatures;

use App\Domains\Documents\Models\Document;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Events\DocumentRecalled;

/**
 * Permet à l'auteur d'un document de rappeler une demande de signature.
 *
 * Conditions :
 *  - Seul l'auteur (ou un admin) peut rappeler ;
 *  - Le document doit être encore en attente (status = pending) ;
 *  - Chaque rappel est enregistré dans l'audit.
 *
 * Le Directeur de Cabinet reçoit une nouvelle notification.
 */
final class RecallSignatureUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $input, User $actor): Document
    {
        $document = Document::query()->findOrFail($input['document_id']);

        // Guard : auteur du document ou admin
        abort_unless(
            $document->author_id === $actor->id || $actor->hasRole('admin'),
            403,
            'Seul l\'auteur du document peut rappeler une demande de signature.'
        );

        // Guard : document toujours en attente
        abort_unless(
            $document->status === 'pending',
            409,
            'Ce document n\'est plus en attente de signature.'
        );

        $document->update([
            'recalled_at' => now(),
        ]);

        // Historique
        $document->histories()->create([
            'user_id' => $actor->id,
            'action' => 'signature_recalled',
            'description' => 'Rappel de signature envoyé au Directeur de Cabinet',
            'metadata' => [
                'recalled_at' => now()->toIso8601String(),
            ],
        ]);

        // Notification au Directeur de Cabinet
        $this->notificationService->notifyFirstUserWithRole(
            'directeur_cabinet',
            'Rappel : document à signer',
            'Le document « ' . $document->subject . ' » (N° ' . $document->document_number . ') attend toujours votre signature.',
            'warning',
            [
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'recalled_at' => now()->toIso8601String(),
                'reminder' => true,
                'action_url' => '/documents/' . $document->id,
            ]
        );

// Diffusion temps réel (WebSocket) au Directeur de Cabinet
        try {
            broadcast(new DocumentRecalled($document, $actor));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast DocumentRecalled : ' . $e->getMessage());
        }

        // Audit
        activity()
            ->causedBy($actor)
            ->performedOn($document)
            ->withProperties([
                'document_number' => $document->document_number,
                'recalled_at' => now()->toIso8601String(),
            ])
            ->log('signature_recalled');

        return $document->fresh()->load('author', 'department');
    }
}
