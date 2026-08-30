<?php

namespace App\Http\Controllers\Api;

use App\Domains\Archives\Models\ArchiveBox;
use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArchiveBoxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $boxes = ArchiveBox::withCount('archives')
            ->when($request->search, function ($q, $term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json(['data' => $boxes]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(
            Access::isAdmin($request->user()) || $request->user()->hasRole('archiviste'),
            403,
            'Vous n\'êtes pas autorisé à créer une boîte d\'archives.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:active,full,archived'],
        ]);

        $box = ArchiveBox::create(array_merge($data, [
            'created_by' => $request->user()->id,
            'code' => 'BOX-' . strtoupper(Str::random(8)),
        ]));

        return response()->json([
            'data' => $box->loadCount('archives'),
        ], 201);
    }

    public function show(ArchiveBox $archive_box): JsonResponse
    {
        return response()->json([
            'data' => $archive_box->load(['archives.document', 'creator'])->loadCount('archives'),
        ]);
    }

    public function update(Request $request, ArchiveBox $archive_box): JsonResponse
    {
        abort_unless(
            Access::isAdmin($request->user()) || $request->user()->hasRole('archiviste'),
            403,
            'Vous n\'êtes pas autorisé à modifier cette boîte d\'archives.'
        );

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:active,full,archived'],
        ]);

        $archive_box->update($data);

        return response()->json([
            'data' => $archive_box->fresh()->loadCount('archives'),
        ]);
    }

    public function destroy(Request $request, ArchiveBox $archive_box): JsonResponse
    {
        abort_unless(
            Access::isAdmin($request->user()) || $request->user()->hasRole('archiviste'),
            403,
            'Vous n\'êtes pas autorisé à supprimer cette boîte d\'archives.'
        );

        if ($archive_box->archives()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une boîte contenant des archives.',
            ], 422);
        }

        $archive_box->delete();

        return response()->json(null, 204);
    }
}
