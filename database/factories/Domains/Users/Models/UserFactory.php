<?php

namespace Database\Factories\Domains\Users\Models;

use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            // Note: table departments is neutralisée pendant les tests (migration dupliquée).
            // On évite donc toute dépendance à deleted_at/SoftDeletes sur departments.
            'department_id' => null,

            'job_title' => $this->faker->optional()->jobTitle(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
