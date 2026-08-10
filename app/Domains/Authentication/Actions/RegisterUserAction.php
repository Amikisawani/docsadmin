<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'department_id' => Arr::get($data, 'department_id'),
            'job_title' => Arr::get($data, 'job_title'),
            'phone' => Arr::get($data, 'phone'),
            'is_active' => true,
        ]);

        // Le guard doit rester celui des rôles applicatifs : un rôle créé sous le
        // guard de la requête courante (« sanctum ») ne serait jamais reconnu
        // par les contrôles d'accès, qui interrogent le guard du modèle User.
        $role = Role::firstOrCreate([
            'name' => Arr::get($data, 'role', 'agent'),
            'guard_name' => Guard::getDefaultName(User::class),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
