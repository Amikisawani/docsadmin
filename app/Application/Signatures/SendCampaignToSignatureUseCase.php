<?php

namespace App\Application\Signatures;

use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Events\CampaignSubmitted;

/**
 * Envoie une campagne de publipostage générée à la signature du Directeur de Cabinet.
 *
 * Conditions :
 *  - Seul le créateur de la campagne (ou un admin) peut l'envoyer à la signature ;
 *  - La campagne doit être générée (statut completed) ;
 *  - La campagne ne doit pas déjà être partie en signature.
 *
 * Le Directeur de Cabinet (seul détenteur du pouvoir de signature) est notifié
 * immédiatement.
 */
final class SendCampaignToSignatureUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $input, User $actor): MailMergeBatch
    {
        $batch = MailMergeBatch::query()->with('document', 'creator')->findOrFail($input['batch_id']);

        // Guard : créateur de la campagne ou admin
        abort_unless(
            $batch->created_by === $actor->id || $actor->hasRole('admin'),
            403,
            'Seul le créateur de la campagne peut l\'envoyer à la signature.'
        );

// Guard : campagne générée (completed, partial) ou en attente de workflow
        // mais dont les documents ont déjà été générés (awaiting_workflow).
        abort_unless(
            in_array($batch->status, ['completed', 'partial', 'awaiting_workflow'], true),
            409,
            'Seules les campagnes générées peuvent être envoyées à la signature.'
        );

        // Guard : pas déjà envoyée
        abort_unless(
            empty($batch->submitted_for_signature_at),
            409,
            'Cette campagne a déjà été envoyée à la signature.'
        );

        $batch->update([
            'status' => 'pending_signature',
            'submitted_for_signature_at' => now(),
            'rejection_reason' => null,
        ]);

        // Notification au Directeur de Cabinet
        $this->notificationService->notifyFirstUserWithRole(
            'directeur_cabinet',
            'Campagne de documents à signer',
            'La campagne « ' . $batch->title . ' » (' . $batch->total_recipients . ' document(s)) vous attend pour signature.',
            'info',
            [
                'batch_id' => $batch->id,
                'title' => $batch->title,
                'recipients_count' => $batch->total_recipients,
                'action_url' => '/director/campaigns/' . $batch->id,
            ]
        );

        // Diffusion temps réel (WebSocket) au Directeur de Cabinet
        try {
            broadcast(new CampaignSubmitted($batch, $actor));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast CampaignSubmitted : ' . $e->getMessage());
        }

// Audit
        activity()
            ->causedBy($actor)
            ->performedOn($batch)
            ->withProperties([
                'title' => $batch->title,
                'recipients_count' => $batch->total_recipients,
            ])
            ->log('campaign_submitted_for_signature');

        return $batch->fresh()->load('document', 'creator', 'recipients');
    }

    /**
     * Notifie uniquement le Directeur de Cabinet qu'une campagne l'attend pour signature.
     * Utilisé lors de l'envoi AUTOMATIQUE après génération (flux Présidence) : le statut
     * de la campagne a déjà été passé à `pending_signature` par l'appelant.
     */
    public function notifyDirector(MailMergeBatch $batch): void
    {
        // Notification au Directeur de Cabinet
        $this->notificationService->notifyFirstUserWithRole(
            'directeur_cabinet',
            'Campagne de documents à signer',
            'La campagne « ' . $batch->title . ' » (' . $batch->total_recipients . ' document(s)) vous attend pour signature.',
            'info',
            [
                'batch_id' => $batch->id,
                'title' => $batch->title,
                'recipients_count' => $batch->total_recipients,
                'action_url' => '/director/campaigns/' . $batch->id,
            ]
        );

        // Diffusion temps réel (WebSocket) au Directeur de Cabinet
        try {
            broadcast(new CampaignSubmitted($batch, $batch->creator));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast CampaignSubmitted (auto) : ' . $e->getMessage());
        }

        // Audit
        activity()
            ->performedOn($batch)
            ->withProperties([
                'title' => $batch->title,
                'recipients_count' => $batch->total_recipients,
            ])
            ->log('campaign_submitted_for_signature');
    }
}
