<?php

namespace App\Application\Signatures;

use App\Domains\Documents\Models\Document;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Events\DocumentRejected;

/**
 * Permet au Directeur de Cabinet de rejeter un document.
 *
 * Conditions :
 *  - Seul le Directeur de Cabinet (détenteur du pouvoir de signature) peut rejeter ;
 *  - Le motif de rejet est obligatoire ;
 *  - Le créateur reçoit une notification ;
 *  - Le document revient dans son espace avec le statut « rejected ».
 */
final class RejectForSignatureUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $input, User $actor): Document
    {
        $document = Document::query()->findOrFail($input['document_id']);

        // Guard : seul le Directeur de Cabinet peut rejeter
        abort_unless(
            $actor->canSignDocuments(),
            403,
            'Seul le Directeur de Cabinet peut rejeter un document.'
        );

        // Guard : motif obligatoire
        $reason = trim((string) ($input['rejection_reason'] ?? ''));
        abort_unless($reason !== '', 422, 'Le motif de rejet est obligatoire.');

        // Guard : document en attente
        abort_unless(
            $document->status === 'pending',
            409,
            'Ce document n\'est pas en attente de signature.'
        );

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Historique
        $document->histories()->create([
            'user_id' => $actor->id,
            'action' => 'signature_rejected',
            'description' => 'Document rejeté par le Directeur de Cabinet : ' . $reason,
            'metadata' => [
                'rejection_reason' => $reason,
                'rejected_by' => $actor->name,
            ],
        ]);

        // Notification au créateur du document
        $author = $document->author;
        if ($author) {
            $this->notificationService->notify(
                $author,
                'Document rejeté',
                'Le document « ' . $document->subject . ' » (N° ' . $document->document_number . ') a été rejeté. Motif : ' . $reason,
                'error',
                [
                    'document_id' => $document->id,
                    'document_number' => $document->document_number,
                    'rejection_reason' => $reason,
                    'action_url' => '/documents/' . $document->id,
                ]
            );
        }

// Diffusion temps réel (WebSocket) à l'auteur du document
        try {
            broadcast(new DocumentRejected($document, $actor, $reason));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast DocumentRejected : ' . $e->getMessage());
        }

        // Audit
        activity()
            ->causedBy($actor)
            ->performedOn($document)
            ->withProperties([
                'document_number' => $document->document_number,
                'rejection_reason' => $reason,
            ])
            ->log('signature_rejected');

        return $document->fresh()->load('author', 'department');
    }
}
