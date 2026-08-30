<?php

namespace App\Http\Controllers\Api;

use App\Domains\Archives\Models\Archive;
use App\Domains\Documents\Models\Document;
use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ArchiveController extends Controller
{
    /**
     * Liste des documents éligibles à l'archivage (non archivés, non supprimés).
     */
    public function eligibleDocuments(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->notArchived()
            ->notDeleted()
            ->with('author:id,name')
            ->when(
                ! Access::isAdmin($request->user()) && ! $request->user()->hasRole('archiviste'),
                fn ($q) => $q->where('author_id', $request->user()->id)
            )
            ->when($request->search, fn ($q, $term) => $q->search($term))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json(['data' => $documents]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Archive::with(['document:id,document_number,subject', 'archiveBox:id,code,name']);

        if (! Access::isAdmin($request->user()) && ! $request->user()->hasRole('archiviste')) {
            $query->whereHas('document', fn ($q) => $q->where('author_id', $request->user()->id));
        }

        if ($request->search) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('reference', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%")
                    ->orWhereHas('document', fn ($dq) => $dq->where('subject', 'like', "%{$term}%")
                        ->orWhere('document_number', 'like', "%{$term}%"));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->box_id) {
            $query->where('archive_box_id', $request->box_id);
        }

        if ($request->boolean('expiring')) {
            $query->whereNotNull('conservation_until')
                ->where('conservation_until', '<=', Carbon::now()->addDays(30));
        }

        $archives = $query->latest('archived_at')->paginate($request->get('per_page', 15));

        return response()->json(['data' => $archives]);
    }

    public function show(Request $request, Archive $archive): JsonResponse
    {
        $document = $archive->document;
        if ($document) {
            Access::ensureCanViewDocument($request->user(), $document);
        } elseif (! Access::isAdmin($request->user()) && ! $request->user()->hasRole('archiviste')) {
            abort(403);
        }
        return response()->json([
            'data' => $archive->load(['document', 'archiveBox', 'archiver']),
        ]);
    }

    /**
     * Désarchiver un document.
     * Cela supprime l'enregistrement d'archive (soft delete) et rend le document disponible.
     */
    public function destroy(Request $request, Archive $archive): JsonResponse
    {
        abort_unless(
            Access::isAdmin($request->user()) || $request->user()->hasRole('archiviste'),
            403,
            'Vous n\'êtes pas autorisé à désarchiver ce document.'
        );

        $document = $archive->document;

        if ($document) {
            // Remettre le document dans son état précédent à l'archivage.
            if ($document->is_archived) {
                $document->update(['is_archived' => false]);
            }
        }

        $archive->delete();

        return response()->json(null, 204);
    }
}
