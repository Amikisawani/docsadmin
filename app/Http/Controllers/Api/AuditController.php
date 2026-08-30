<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());

        $logs = Activity::with('causer')
            ->when($request->search, fn($q, $term) => $q->where('description', 'like', "%{$term}%"))
            ->when($request->event, fn($q, $event) => $q->where('description', $event))
            ->when($request->user_id, fn($q, $id) => $q->where('causer_id', $id))
            ->when($request->date_from, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json(['data' => $logs]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());
        $log = Activity::with('causer')->findOrFail($id);
        return response()->json(['data' => $log]);
    }

    public function events(Request $request): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());

        $events = Activity::select('description')
            ->distinct()
            ->orderBy('description')
            ->pluck('description');

        return response()->json(['data' => $events]);
    }

    public function stats(Request $request): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());

        $stats = [
            'total_logs' => Activity::count(),
            'logs_by_event' => Activity::selectRaw('description, count(*) as total')
                ->groupBy('description')
                ->orderByDesc('total')
                ->limit(20)
                ->pluck('total', 'description'),
            'logs_today' => Activity::whereDate('created_at', today())->count(),
            'logs_this_week' => Activity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'top_users' => Activity::selectRaw('causer_id, count(*) as total')
                ->whereNotNull('causer_id')
                ->groupBy('causer_id')
                ->orderByDesc('total')
                ->limit(10)
                ->with('causer:id,name')
                ->get()
                ->map(fn($log) => [
                    'user' => $log->causer?->name ?? 'Inconnu',
                    'actions' => $log->total,
                ]),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Historique des signatures (logs d'activité liés à la signature).
     */
    public function signatures(Request $request): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());

        $logs = Activity::with('causer')
            ->whereIn('description', ['signed', 'signature_created', 'signature_recalled'])
            ->when($request->search, fn($q, $term) => $q->where('properties', 'like', "%{$term}%"))
            ->when($request->user_id, fn($q, $id) => $q->where('causer_id', $id))
            ->when($request->date_from, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json(['data' => $logs]);
    }

    /**
     * Historique des rejets (logs d'activité liés aux rejets).
     */
    public function rejections(Request $request): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());

        $logs = Activity::with('causer')
            ->whereIn('description', ['signature_rejected', 'workflow_rejected', 'document_rejected'])
            ->when($request->search, fn($q, $term) => $q->where('properties', 'like', "%{$term}%"))
            ->when($request->user_id, fn($q, $id) => $q->where('causer_id', $id))
            ->when($request->date_from, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json(['data' => $logs]);
    }

    /**
     * Activité du Directeur de Cabinet (toutes ses actions journalisées).
     */
    public function directorActivity(Request $request): JsonResponse
    {
        Access::ensureCanReadAudit($request->user());

        $director = \App\Domains\Users\Models\User::role('directeur_cabinet')->first();

        $logs = Activity::with('causer')
            ->when($director, fn($q) => $q->where('causer_id', $director->id))
            ->when($request->search, fn($q, $term) => $q->where('description', 'like', "%{$term}%"))
            ->when($request->date_from, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json(['data' => $logs]);
    }
}
