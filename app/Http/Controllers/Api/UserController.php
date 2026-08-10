<?php

namespace App\Http\Controllers\Api;

use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('department', 'roles')
            ->when($request->search, fn($q, $term) => $q->where(function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            }))
            ->when($request->department_id, fn($q, $id) => $q->byDepartment($id))
            ->when($request->role, fn($q, $role) => $q->role($role))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderBy($request->sort ?? 'name', $request->order ?? 'asc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        if (isset($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user->load('department', 'roles'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('department', 'roles', 'permissions', 'signatures')
            ->findOrFail($id);

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['sometimes', 'string', 'min:8'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => $user->fresh()->load('department', 'roles'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->documents()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cet utilisateur car il a des documents associés.',
            ], 409);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        return response()->json([
            'message' => 'Avatar mis à jour avec succès.',
            'data' => ['avatar_url' => $user->avatar_url],
        ]);
    }

    public function uploadSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:png', 'max:1024'],
        ]);

        $user = $request->user();
        $path = $request->file('signature')->store('signatures', 'public');

        if ($user->signature_image_path) {
            Storage::disk('public')->delete($user->signature_image_path);
        }

        $user->update(['signature_image_path' => $path]);

        return response()->json([
            'message' => 'Signature mise à jour avec succès.',
            'data' => ['signature_url' => $user->signature_url],
        ]);
    }
}