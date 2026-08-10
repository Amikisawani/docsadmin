<?php

namespace App\Http\Controllers\Api;

use App\Domains\Notifications\Models\NotificationPreference;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->unread, fn ($q) => $q->whereNull('read_at'))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json(['data' => $notifications]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now(), 'is_read' => true]);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now(), 'is_read' => true]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->whereNull('read_at')->count();

        return response()->json(['data' => ['unread_count' => $count]]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)->get();

        return response()->json(['data' => $preferences]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.type' => ['required', 'string'],
            'preferences.*.email' => ['boolean'],
            'preferences.*.database' => ['boolean'],
            'preferences.*.sms' => ['boolean'],
            'preferences.*.push' => ['boolean'],
        ]);

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $request->user()->id, 'type' => $pref['type']],
                [
                    'email' => $pref['email'] ?? true,
                    'database' => $pref['database'] ?? true,
                    'sms' => $pref['sms'] ?? false,
                    'push' => $pref['push'] ?? false,
                ]
            );
        }

        return response()->json([
            'message' => 'Préférences mises à jour.',
            'data' => NotificationPreference::where('user_id', $request->user()->id)->get(),
        ]);
    }
}
