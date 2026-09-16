<?php

namespace App\Http\Controllers\Api;

use App\Domains\Authentication\Actions\RegisterUserAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUserAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sort = Access::sanitizeSort($request->sort, ['name', 'email', 'created_at', 'job_title'], 'name');
        $order = Access::sanitizeOrder($request->order ?? 'asc');

        $users = User::with('department', 'roles')
            ->when($request->search, fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            }))
            ->when($request->department_id, fn ($q, $id) => $q->byDepartment($id))
            ->when($request->role, fn ($q, $role) => $q->role($role))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy($sort, $order)
            ->paginate(Access::perPage($request->per_page));

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        Access::ensureCanManageUsers($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $user = $this->registerUserAction->execute($validated, $request->user());

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user->load('department', 'roles'),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::with('department', 'roles', 'permissions', 'signatures')
            ->findOrFail($id);

        $actor = $request->user();
        abort_unless(
            Access::isAdmin($actor) || (string) $user->id === (string) $actor->id,
            403,
            'Vous n\'êtes pas autorisé à consulter cet utilisateur.'
        );

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $actor = $request->user();
        $isSelf = (string) $user->id === (string) $actor->id;

        abort_unless(
            Access::isAdmin($actor) || $isSelf,
            403,
            'Vous n\'êtes pas autorisé à modifier cet utilisateur.'
        );

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if (Access::isAdmin($actor)) {
            $rules['email'] = ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$id];
            $rules['department_id'] = ['nullable', 'string', 'exists:departments,id'];
            $rules['is_active'] = ['sometimes', 'boolean'];
            $rules['role'] = ['nullable', 'string', 'exists:roles,name'];
        }

        $validated = $request->validate($rules);

        if (isset($validated['password'])) {
            $user->password = $validated['password'];
            unset($validated['password']);
        }

        $role = $validated['role'] ?? null;
        unset($validated['role']);

        $user->fill($validated);
        $user->save();

        if (Access::isAdmin($actor) && $role) {
            $user->syncRoles([$role]);
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => $user->fresh()->load('department', 'roles'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        Access::ensureCanManageUsers($request->user());

        $user = User::findOrFail($id);

        abort_if(
            (string) $user->id === (string) $request->user()->id,
            403,
            'Vous ne pouvez pas supprimer votre propre compte.'
        );

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

    public function uploadAvatar(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        abort_unless(
            Access::isAdmin($actor) || (string) $user->id === (string) $actor->id,
            403,
            'Vous n\'êtes pas autorisé à modifier cet avatar.'
        );

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

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

    public function uploadSignature(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        abort_unless(
            Access::isAdmin($actor) || (string) $user->id === (string) $actor->id,
            403,
            'Vous n\'êtes pas autorisé à modifier cette signature.'
        );

        $request->validate([
            'signature' => ['required', 'image', 'mimes:png', 'max:1024'],
        ]);

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
