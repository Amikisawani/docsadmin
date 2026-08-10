<?php

namespace App\Application\Signatures;

use App\Domains\Documents\Models\Document;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Events\DocumentSubmitted;
use Illuminate\Support\Facades\Log;

/**
 * Envoie un document à la signature du Directeur de Cabinet.
 *
 * Seul l'auteur du document (ou un administrateur) peut envoyer un document
 * à la signature. Le document doit être en statut « draft » ou « rejected ».
 *
 * Le Directeur de Cabinet (seul détenteur du pouvoir de signature) est
 * notifié immédiatement.
 */
final class SubmitForSignatureUseCase
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
            'Seul l\'auteur du document peut l\'envoyer à la signature.'
        );

        // Guard : statut autorisé
        abort_unless(
            in_array($document->status, ['draft', 'rejected']),
            409,
            'Ce document ne peut pas être envoyé à la signature dans son état actuel.'
        );

        $document->update([
            'status' => 'pending',
            'submitted_for_signature_at' => now(),
            'priority' => $input['priority'] ?? $document->priority ?? 'normale',
            'deadline' => $input['deadline'] ?? $document->deadline ?? null,
            'rejection_reason' => null,
        ]);

        // Historique
        $document->histories()->create([
            'user_id' => $actor->id,
            'action' => 'submitted_for_signature',
            'description' => 'Document envoyé à la signature du Directeur de Cabinet',
            'metadata' => [
                'priority' => $document->priority,
                'deadline' => $document->deadline ? $document->deadline->toDateString() : null,
            ],
        ]);

        // Notification au Directeur de Cabinet
        $this->notificationService->notifyFirstUserWithRole(
            'directeur_cabinet',
            'Nouveau document à signer',
            'Le document « '.$document->subject.' » (N° '.$document->document_number.') vous attend pour signature.',
            'info',
            [
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'priority' => $document->priority,
                'deadline' => $document->deadline ? $document->deadline->toDateString() : null,
                'action_url' => '/documents/'.$document->id,
            ]
        );

        // Diffusion temps réel (WebSocket) au Directeur de Cabinet
        try {
            broadcast(new DocumentSubmitted($document, $actor));
        } catch (\Throwable $e) {
            Log::warning('Broadcast DocumentSubmitted : '.$e->getMessage());
        }

        // Audit
        activity()
            ->causedBy($actor)
            ->performedOn($document)
            ->withProperties([
                'document_number' => $document->document_number,
                'priority' => $document->priority,
                'deadline' => $document->deadline ? $document->deadline->toDateString() : null,
            ])
            ->log('submitted_for_signature');

        return $document->fresh()->load('author', 'department');
    }
}
