<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Users\Models\User;
use App\Support\Access;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RegisterUserAction
{
    public const DEFAULT_ROLE = 'agent_administration';

    public function execute(array $data, User $actor): User
    {
        Access::ensureCanManageUsers($actor);

        $roleName = (string) Arr::get($data, 'role', self::DEFAULT_ROLE);

        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => ['Rôle invalide.'],
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'department_id' => Arr::get($data, 'department_id'),
            'job_title' => Arr::get($data, 'job_title'),
            'phone' => Arr::get($data, 'phone'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
