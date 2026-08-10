<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Users\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Helper centralisé pour créer des notifications en base
 * (table `notifications` personnalisée de l'application).
 */
final class NotificationService
{
    /**
     * Crée une notification destinée à un utilisateur.
     *
     * @param  User  $user  Destinataire
     * @param  string  $title  Titre
     * @param  string|null  $body  Corps
     * @param  string  $type  info | warning | success | error
     * @param  array|null  $data  Données additionnelles (document_id, workflow_instance_id, etc.)
     */
    public function notify(User $user, string $title, ?string $body = null, string $type = 'info', ?array $data = null): void
    {
        $notification = new DatabaseNotification;
        $notification->id = (string) Str::ulid();
        $notification->type = $type;
        $notification->title = $title;
        $notification->body = $body;
        $notification->data = $data;
        $notification->channel = 'database';
        $notification->is_read = false;
        $notification->read_at = null;
        $notification->notifiable_id = $user->id;
        $notification->notifiable_type = $user->getMorphClass();
        $notification->timestamps = true;
        $notification->save();
    }

    /**
     * Notifie tous les utilisateurs possédant un rôle donné.
     */
    public function notifyRole(string $role, string $title, ?string $body = null, string $type = 'info', ?array $data = null): void
    {
        try {
            User::role($role)
                ->where('is_active', true)
                ->get()
                ->each(fn (User $user) => $this->notify($user, $title, $body, $type, $data));
        } catch (\Throwable) {
            // Le rôle peut ne pas exister dans l'environnement courant ; on ignore la notification sans casser le workflow.
        }
    }

    /**
     * Notifie le premier utilisateur possédant un rôle donné.
     */
    public function notifyFirstUserWithRole(string $role, string $title, ?string $body = null, string $type = 'info', ?array $data = null): void
    {
        try {
            $user = User::role($role)->where('is_active', true)->first();
            if ($user) {
                $this->notify($user, $title, $body, $type, $data);
            }
        } catch (\Throwable) {
            // Le rôle peut ne pas exister dans l'environnement courant ; on ignore la notification sans casser le workflow.
        }
    }
}
