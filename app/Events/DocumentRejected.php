<?php

namespace App\Events;

use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement temps réel : un document a été rejeté par le Directeur de Cabinet.
 * Destiné à l'auteur du document.
 */
class DocumentRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Document $document,
        public User $rejectedBy,
        public string $reason,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->document->author_id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->document->author_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'document.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'document_id' => $this->document->id,
            'document_number' => $this->document->document_number,
            'subject' => $this->document->subject,
            'rejected_by' => $this->rejectedBy->name,
            'reason' => $this->reason,
            'action_url' => '/documents/' . $this->document->id,
        ];
    }
}
