<?php

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        $user = User::factory()->create(['email' => 'cible@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'mauvais-mot-de-passe',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'mauvais-mot-de-passe',
        ])->assertStatus(429);
    }
}
