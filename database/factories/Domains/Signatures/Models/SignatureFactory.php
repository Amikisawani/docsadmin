<?php

namespace Database\Factories\Domains\Signatures\Models;

use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SignatureFactory extends Factory
{
    protected $model = Signature::class;

    public function definition(): array
    {
        return [
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'type' => 'graphical',
            'label' => $this->faker->word(),
            'image_path' => null,
            'certificate_data' => null,
            'certificate_serial' => null,
            'certificate_expires_at' => null,
            'hash_algorithm' => 'sha256',
            'is_default' => false,
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
