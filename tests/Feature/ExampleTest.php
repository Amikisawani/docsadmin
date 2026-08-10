<?php

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }

    /**
     * La racine redirige un visiteur non authentifie vers la page de login.
     */
    public function test_root_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * La page de login est servie (SPA) et retourne 200.
     */
    public function test_login_page_returns_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Un utilisateur authentifie accede a la SPA sans redirection.
     */
    public function test_authenticated_user_accesses_app(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

