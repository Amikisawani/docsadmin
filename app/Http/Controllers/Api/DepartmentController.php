<?php

namespace App\Http\Controllers\Api;

use App\Domains\Departments\Models\Department;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $departments = Department::with('parent', 'children')
            ->when($request->type, fn ($q, $type) => $q->ofType($type))
            ->when($request->search, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json(['data' => $departments]);
    }

    public function tree(): JsonResponse
    {
        $departments = Department::with('children.children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $departments]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments'],
            'parent_id' => ['nullable', 'string', 'exists:departments,id'],
            'type' => ['required', 'string', 'in:ministere,departement,direction,service,unite'],
            'description' => ['nullable', 'string'],
        ]);

        $department = Department::create($validated);

        return response()->json([
            'message' => 'Département créé avec succès.',
            'data' => $department->load('parent'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $department = Department::with('parent', 'children', 'users')->findOrFail($id);

        return response()->json(['data' => $department]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:departments,code,'.$id],
            'parent_id' => ['nullable', 'string', 'exists:departments,id'],
            'type' => ['sometimes', 'string', 'in:ministere,departement,direction,service,unite'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $department->update($validated);

        return response()->json([
            'message' => 'Département mis à jour avec succès.',
            'data' => $department->fresh()->load('parent'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        if ($department->users()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer ce département car il contient des utilisateurs.',
            ], 409);
        }

        $department->delete();

        return response()->json([
            'message' => 'Département supprimé avec succès.',
        ]);
    }
}
