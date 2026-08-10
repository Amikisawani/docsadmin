<?php

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Tests\TestCase;

class SignatureAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
        $this->artisan('db:seed', ['--env' => 'testing', '--database' => 'sqlite', '--class' => 'Database\\Seeders\\RoleAndPermissionSeeder'])->run();
    }

    public function test_can_sign_endpoint_returns_false_for_agent(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent_administration');

        $this->actingAs($agent, 'sanctum');

        $res = $this->getJson('/api/v1/signatures/can-sign');
        $res->assertOk();
        $res->assertJsonPath('data.can_sign', false);
    }

    public function test_can_sign_endpoint_returns_false_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum');

        // Version Présidence : l'administrateur ne signe pas les documents.
        $res = $this->getJson('/api/v1/signatures/can-sign');
        $res->assertOk();
        $res->assertJsonPath('data.can_sign', false);
    }

    public function test_can_sign_endpoint_returns_false_for_director(): void
    {
        $director = User::factory()->create();
        $director->assignRole('directeur');

        $this->actingAs($director, 'sanctum');

        // Version Présidence : le Directeur (hors Cabinet) ne signe pas les documents.
        $res = $this->getJson('/api/v1/signatures/can-sign');
        $res->assertOk();
        $res->assertJsonPath('data.can_sign', false);
    }

    public function test_can_sign_endpoint_returns_true_for_director_cabinet(): void
    {
        $directorCabinet = User::factory()->create();
        $directorCabinet->assignRole('directeur_cabinet');

        $this->actingAs($directorCabinet, 'sanctum');

        // Version Présidence : le Directeur de Cabinet est le SEUL signataire.
        $res = $this->getJson('/api/v1/signatures/can-sign');
        $res->assertOk();
        $res->assertJsonPath('data.can_sign', true);
    }
}
