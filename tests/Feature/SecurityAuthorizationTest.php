<?php

namespace Tests\Feature;

use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleAndPermissionSeeder'])->run();
    }

    private function agent(): User
    {
        $user = User::factory()->create();
        $user->assignRole('agent_administration');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_public_registration_is_disabled(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(405);
        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.com']);
    }

    public function test_login_and_director_pending_count_require_authentication(): void
    {
        $this->getJson('/api/v1/auth/director-pending-count')->assertUnauthorized();
        $this->getJson('/api/v1/users')->assertUnauthorized();
        $this->getJson('/api/v1/audit')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard/stats')->assertUnauthorized();
    }

    public function test_agent_cannot_create_or_privilege_escalate_users(): void
    {
        $agent = $this->agent();
        $this->actingAs($agent, 'sanctum');

        $this->postJson('/api/v1/users', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_agent_cannot_update_another_user_or_assign_admin_role(): void
    {
        $agent = $this->agent();
        $other = User::factory()->create();
        $this->actingAs($agent, 'sanctum');

        $this->putJson('/api/v1/users/'.$other->id, [
            'name' => 'Hijacked',
            'role' => 'admin',
        ])->assertForbidden();

        $this->putJson('/api/v1/users/'.$agent->id, [
            'role' => 'admin',
        ])->assertOk();

        $agent->refresh();
        $this->assertFalse($agent->hasRole('admin'));
        $this->assertTrue($agent->hasRole('agent_administration'));
    }

    public function test_agent_cannot_view_or_update_another_users_document(): void
    {
        $agent = $this->agent();
        $owner = User::factory()->create();
        $document = Document::factory()->create([
            'author_id' => $owner->id,
            'status' => 'draft',
            'subject' => 'Secret note',
        ]);

        $this->actingAs($agent, 'sanctum');

        $this->getJson('/api/v1/documents/'.$document->id)->assertForbidden();
        $this->putJson('/api/v1/documents/'.$document->id, [
            'subject' => 'Hijacked',
            'status' => 'signed',
        ])->assertForbidden();
        $this->deleteJson('/api/v1/documents/'.$document->id)->assertForbidden();
        $this->getJson('/api/v1/documents/'.$document->id.'/history')->assertForbidden();
        $this->getJson('/api/v1/documents/'.$document->id.'/workflow-progress')->assertForbidden();

        $document->refresh();
        $this->assertSame('Secret note', $document->subject);
        $this->assertSame('draft', $document->status);
        $this->assertFalse((bool) $document->is_deleted);
    }

    public function test_agent_cannot_forge_signed_status_on_own_document(): void
    {
        $agent = $this->agent();
        $document = Document::factory()->create([
            'author_id' => $agent->id,
            'status' => 'draft',
        ]);

        $this->actingAs($agent, 'sanctum');

        $this->putJson('/api/v1/documents/'.$document->id, [
            'status' => 'signed',
        ])->assertOk();

        $document->refresh();
        $this->assertSame('draft', $document->status);
    }

    public function test_agent_cannot_read_audit_or_roles(): void
    {
        $agent = $this->agent();
        $this->actingAs($agent, 'sanctum');

        $this->getJson('/api/v1/audit')->assertForbidden();
        $this->getJson('/api/v1/audit/stats')->assertForbidden();
        $this->getJson('/api/v1/roles')->assertForbidden();
        $this->getJson('/api/v1/permissions')->assertForbidden();
    }

    public function test_admin_can_create_user_with_existing_role_only(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/v1/users', [
            'name' => 'New Agent',
            'email' => 'new-agent@example.com',
            'password' => 'password123',
            'role' => 'agent_administration',
        ])->assertCreated();

        $this->assertTrue(User::where('email', 'new-agent@example.com')->first()->hasRole('agent_administration'));

        $this->postJson('/api/v1/users', [
            'name' => 'Ghost',
            'email' => 'ghost@example.com',
            'password' => 'password123',
            'role' => 'super-root-does-not-exist',
        ])->assertStatus(422);

        $this->assertNull(Role::where('name', 'super-root-does-not-exist')->first());
    }

    public function test_agent_cannot_read_or_modify_another_users_signature(): void
    {
        $agent = $this->agent();
        $owner = User::factory()->create();
        $signature = Signature::factory()->create([
            'user_id' => $owner->id,
            'type' => 'graphical',
            'is_active' => true,
        ]);

        $this->actingAs($agent, 'sanctum');

        $this->getJson('/api/v1/signatures/'.$signature->id)->assertForbidden();
        $this->putJson('/api/v1/signatures/'.$signature->id, [
            'is_default' => true,
        ])->assertForbidden();
        $this->deleteJson('/api/v1/signatures/'.$signature->id)->assertForbidden();
    }

    public function test_agent_cannot_create_workflows_or_departments(): void
    {
        $agent = $this->agent();
        $this->actingAs($agent, 'sanctum');

        $this->postJson('/api/v1/workflows', [
            'name' => 'Rogue workflow',
            'document_type' => 'note',
            'steps' => [['name' => 'chef', 'role' => 'admin', 'order' => 1]],
        ])->assertForbidden();

        $this->postJson('/api/v1/departments', [
            'name' => 'Rogue dept',
            'code' => 'ROGUE',
            'type' => 'service',
        ])->assertForbidden();
    }

    public function test_dashboard_stats_are_scoped_for_agents(): void
    {
        $agent = $this->agent();
        $other = User::factory()->create();

        Document::factory()->create(['author_id' => $agent->id, 'status' => 'draft']);
        Document::factory()->count(3)->create(['author_id' => $other->id, 'status' => 'draft']);

        $this->actingAs($agent, 'sanctum');

        $res = $this->getJson('/api/v1/dashboard/stats');
        $res->assertOk();
        $res->assertJsonPath('data.total_documents', 1);
        $res->assertJsonPath('data.total_users', 1);
    }

    public function test_sort_parameter_is_allowlisted(): void
    {
        $agent = $this->agent();
        $this->actingAs($agent, 'sanctum');

        $this->getJson('/api/v1/documents?sort=author_id;(select password from users)&order=desc')
            ->assertOk();
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
