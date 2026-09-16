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
     * La racine sert le shell SPA. L'auth est gérée côté Vue (token Sanctum),
     * pas par une redirection de session web — sinon le tableau de bord se
     * recharge en boucle pour un utilisateur déjà connecté via l'API.
     */
    public function test_root_serves_spa_shell_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="app"', false);
        $this->assertFalse($response->isRedirect());
    }

    public function test_spa_deep_link_serves_shell_without_session_redirect(): void
    {
        $response = $this->get('/documents');

        $response->assertOk();
        $this->assertFalse($response->isRedirect());
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

