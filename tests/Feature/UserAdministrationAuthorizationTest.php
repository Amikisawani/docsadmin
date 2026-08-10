<?php

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAdministrationAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
        $this->artisan('db:seed', ['--env' => 'testing', '--database' => 'sqlite', '--class' => 'Database\\Seeders\\RoleAndPermissionSeeder'])->run();
    }

    public function test_guest_cannot_register_an_account(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Intrus',
            'email' => 'intrus@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('users', ['email' => 'intrus@example.test']);
    }

    public function test_non_admin_cannot_register_an_account(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent_administration');
        $this->actingAs($agent, 'sanctum');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Intrus',
            'email' => 'intrus@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intrus@example.test']);
    }

    public function test_admin_registers_an_account_with_the_application_guard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nouvel agent',
            'email' => 'agent@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agent_administration',
        ])->assertCreated();

        $created = User::where('email', 'agent@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole('agent_administration'));
    }

    public function test_non_admin_cannot_create_or_delete_users(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent_administration');
        $target = User::factory()->create();
        $this->actingAs($agent, 'sanctum');

        $this->postJson('/api/v1/users', [
            'name' => 'Compte forcé',
            'email' => 'force@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/users/{$target->id}")->assertForbidden();
        $this->putJson("/api/v1/users/{$target->id}", ['role' => 'admin'])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'force@example.test']);
        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    public function test_user_cannot_upload_a_signature_for_someone_else(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent_administration');
        $other = User::factory()->create();
        Storage::fake('public');
        $this->actingAs($agent, 'sanctum');

        $this->post("/api/v1/users/{$other->id}/signature", [
            'signature' => UploadedFile::fake()->image('signature.png'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->post("/api/v1/users/{$other->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }
}
