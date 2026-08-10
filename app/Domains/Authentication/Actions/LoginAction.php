<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est désactivé. Contactez l\'administrateur.'],
            ]);
        }

        // Revoke old tokens
        $user->tokens()->delete();

        $token = $user->createToken('api-token', $user->getPermissionNames()->toArray())->plainTextToken;

        // Log login
        activity()
            ->causedBy($user)
            ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
            ->log('login');

        return [
            'user' => $user->load('department', 'roles', 'permissions'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        activity()
            ->causedBy($user)
            ->log('logout');

        $user->currentAccessToken()->delete();
    }
}