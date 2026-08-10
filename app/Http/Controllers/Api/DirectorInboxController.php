<?php

namespace App\Http\Controllers\Api;

use App\Domains\Documents\Models\Document;
use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Boîte de réception du Directeur de Cabinet.
 *
 * Le Directeur de Cabinet ne voit que les documents qui lui sont destinés
 * pour décision : documents en attente de signature, urgents, nouveaux reçus
 * et récemment rejetés.
 */
class DirectorInboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Guard : seul le Directeur de Cabinet accède à cette boîte
        abort_unless($user->hasRole('directeur_cabinet'), 403, 'Accès réservé au Directeur de Cabinet.');

        $query = Document::with(['author', 'department'])
            ->notDeleted()
            ->notArchived()
            ->where('status', 'pending')
            ->whereNotNull('submitted_for_signature_at');

        // Filtres optionnels
        $query
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->search, fn ($q, $term) => $q->search($term))
            ->when($request->type, fn ($q, $type) => $q->byType($type))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('submitted_for_signature_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('submitted_for_signature_at', '<=', $date));

        $order = $request->order ?? 'desc';
        $sort = $request->sort ?? 'submitted_for_signature_at';

        $documents = $query->orderBy($sort, $order)->paginate($request->per_page ?? 20);

        // Campagnes de publipostage envoyées à la signature (à signer par le Directeur de Cabinet).
        // On inclut aussi les campagnes générées (completed / awaiting_workflow avec documents
        // générés) pour qu'elles apparaissent tant qu'elles n'ont pas été prises en charge.
        $campaigns = MailMergeBatch::with(['creator'])
            ->where(function ($q) {
                $q->whereIn('status', ['pending_signature', 'signed', 'rejected'])
                    ->orWhere(function ($q2) {
                        $q2->whereIn('status', ['completed', 'partial', 'awaiting_workflow'])
                            ->where('generated_count', '>', 0);
                    });
            })
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $documents,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Statistiques de la boîte de réception (pour le tableau de bord du Directeur).
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->hasRole('directeur_cabinet'), 403, 'Accès réservé au Directeur de Cabinet.');

        $base = Document::query()->notDeleted()->notArchived()->whereNotNull('submitted_for_signature_at');

        $stats = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'urgent' => (clone $base)->where('status', 'pending')->where('priority', 'urgente')->count(),
            'high' => (clone $base)->where('status', 'pending')->where('priority', 'haute')->count(),
            'new_today' => (clone $base)->where('status', 'pending')->whereDate('submitted_for_signature_at', today())->count(),
            'recent_rejected' => Document::query()->notDeleted()
                ->whereIn('status', ['rejected'])
                ->whereDate('updated_at', '>=', now()->subDays(7))
                ->count(),
            'signed' => (clone $base)->where('status', 'signed')->count(),
            'campaigns_pending' => MailMergeBatch::where('status', 'pending_signature')->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Nombre de documents en attente pour le Directeur de Cabinet.
     * Utilisé notamment par la page de connexion pour afficher « X documents en attente ».
     */
    public function pendingCount(): JsonResponse
    {
        $director = User::role('directeur_cabinet')->where('is_active', true)->first();

        // Cette route est publique (pré-connexion) : on ne renvoie qu'un nombre agrégé.
        $count = 0;
        $newToday = 0;
        if ($director) {
            $base = Document::query()
                ->notDeleted()
                ->notArchived()
                ->whereNotNull('submitted_for_signature_at');
            $count = (clone $base)->where('status', 'pending')->count();
            $newToday = (clone $base)->where('status', 'pending')->whereDate('submitted_for_signature_at', today())->count();
        }

        return response()->json([
            'data' => [
                'pending_count' => $count,
                'new_today_count' => $newToday,
                'has_director' => $director !== null,
            ],
        ]);
    }

    /**
     * Documents rejetés récemment par le Directeur de Cabinet.
     */
    public function recentRejected(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->hasRole('directeur_cabinet'), 403, 'Accès réservé au Directeur de Cabinet.');

        $documents = Document::with(['author', 'department'])
            ->notDeleted()
            ->notArchived()
            ->where('status', 'rejected')
            ->whereNotNull('submitted_for_signature_at')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['data' => $documents]);
    }

    /**
     * Historique de l'activité du Directeur de Cabinet.
     *
     * Regroupe l'ensemble des décisions prises par le Directeur de Cabinet :
     * documents signés, rejetés (avec motif), rappelés, en attente, ainsi que
     * les campagnes de publipostage (signées / rejetées / en attente).
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->hasRole('directeur_cabinet'), 403, 'Accès réservé au Directeur de Cabinet.');

        $events = collect();

        // Documents signés
        $signed = Document::with(['author', 'department'])
            ->notDeleted()
            ->where('status', 'signed')
            ->whereNotNull('submitted_for_signature_at')
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (Document $d) => [
                'type' => 'document',
                'action' => 'signed',
                'label' => 'Document signé',
                'title' => $d->subject,
                'reference' => $d->document_number,
                'author' => $d->author?->name,
                'date' => $d->updated_at,
                'route' => '/director/documents/'.$d->id,
            ]);

        // Documents rejetés
        $rejected = Document::with(['author', 'department'])
            ->notDeleted()
            ->where('status', 'rejected')
            ->whereNotNull('submitted_for_signature_at')
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (Document $d) => [
                'type' => 'document',
                'action' => 'rejected',
                'label' => 'Document rejeté',
                'title' => $d->subject,
                'reference' => $d->document_number,
                'author' => $d->author?->name,
                'reason' => $d->rejection_reason,
                'date' => $d->updated_at,
                'route' => '/director/documents/'.$d->id,
            ]);

        // Documents en attente
        $pending = Document::with(['author', 'department'])
            ->notDeleted()
            ->notArchived()
            ->where('status', 'pending')
            ->whereNotNull('submitted_for_signature_at')
            ->orderBy('submitted_for_signature_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (Document $d) => [
                'type' => 'document',
                'action' => 'pending',
                'label' => 'En attente de signature',
                'title' => $d->subject,
                'reference' => $d->document_number,
                'author' => $d->author?->name,
                'date' => $d->submitted_for_signature_at,
                'route' => '/director/documents/'.$d->id,
            ]);

        // Campagnes de publipostage (toutes)
        $campaigns = MailMergeBatch::with(['creator', 'document'])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (MailMergeBatch $b) => [
                'type' => 'campaign',
                'action' => match ($b->status) {
                    'pending_signature' => 'pending',
                    'signed' => 'signed',
                    'rejected' => 'rejected',
                    default => 'generated',
                },
                'label' => match ($b->status) {
                    'pending_signature' => 'Campagne en attente de signature',
                    'signed' => 'Campagne signée',
                    'rejected' => 'Campagne rejetée',
                    default => 'Campagne générée',
                },
                'title' => $b->title ?: ($b->document?->subject ?: 'Campagne de publipostage'),
                'reference' => 'Publipostage',
                'author' => $b->creator?->name,
                'reason' => $b->rejection_reason,
                'date' => $b->updated_at,
                'route' => '/director/campaigns/'.$b->id,
            ]);

        $events = $signed->concat($rejected)->concat($pending)->concat($campaigns)
            ->sortByDesc('date')
            ->values();

        // Filtre par action si demandé
        if ($request->action && in_array($request->action, ['signed', 'rejected', 'pending'], true)) {
            $events = $events->where('action', $request->action)->values();
        }

        return response()->json([
            'data' => $events,
            'counts' => [
                'signed' => $signed->count(),
                'rejected' => $rejected->count(),
                'pending' => $pending->count(),
                'campaigns' => $campaigns->count(),
            ],
        ]);
    }
}
