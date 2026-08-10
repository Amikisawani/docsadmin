<?php

namespace App\Domains\Authentication\Actions;

use App\Domains\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
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

        $role = Role::firstOrCreate(['name' => Arr::get($data, 'role', 'agent'), 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        return $user;
    }
}
