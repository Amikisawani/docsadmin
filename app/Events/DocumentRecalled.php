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
 * Événement temps réel : rappel de signature envoyé au Directeur de Cabinet.
 */
class DocumentRecalled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Document $document,
        public User $recalledBy,
    ) {}

    public function broadcastOn(): array
    {
        $director = \App\Domains\Users\Models\User::role('directeur_cabinet')->where('is_active', true)->first();

        $channels = [];

        if ($director) {
            $channels[] = new PrivateChannel('App.Models.User.' . $director->id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'document.recalled';
    }

    public function broadcastWith(): array
    {
        return [
            'document_id' => $this->document->id,
            'document_number' => $this->document->document_number,
            'subject' => $this->document->subject,
            'recalled_by' => $this->recalledBy->name,
            'reminder' => true,
            'action_url' => '/director/documents/' . $this->document->id,
        ];
    }
}
