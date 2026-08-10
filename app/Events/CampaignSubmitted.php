<?php

namespace App\Events;

use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\Users\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement temps réel : une campagne de publipostage a été envoyée
 * à la signature du Directeur de Cabinet.
 */
class CampaignSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MailMergeBatch $batch,
        public User $submittedBy,
    ) {}

    public function broadcastOn(): array
    {
        $director = User::role('directeur_cabinet')->where('is_active', true)->first();

        $channels = [];

        if ($director) {
            $channels[] = new PrivateChannel('App.Models.User.'.$director->id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'campaign.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'batch_id' => $this->batch->id,
            'title' => $this->batch->title,
            'document_number' => $this->batch->document?->document_number,
            'recipients_count' => $this->batch->total_recipients,
            'submitted_by' => $this->submittedBy->name,
            'action_url' => '/director/campaigns/'.$this->batch->id,
        ];
    }
}
