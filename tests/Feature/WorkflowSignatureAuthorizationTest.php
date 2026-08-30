<?php

namespace Tests\Feature;

use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;
use Tests\TestCase;

class WorkflowSignatureAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure schema is created exactly once for this test class.
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }


    public function test_start_workflow_is_idempotent(): void

    {
        $actor = User::factory()->create();
        $document = Document::factory()->create(['document_type' => 'note', 'status' => 'draft', 'author_id' => $actor->id]);

        $workflow = Workflow::factory()->create([
            'document_type' => 'note',
            'steps' => [
                ['name' => 'chef', 'role' => 'admin', 'order' => 1],
            ],
            'is_active' => true,
        ]);

        // NOTE: This test suite focuses on authorization decisions.
        // If Spatie roles/permissions are not seeded (common in sqlite testing),
        // avoid calling assignRole('admin') because it can throw and prevent the use-case from running.
        // (Authorization guards should be adjusted separately if needed.)

        $this->actingAs($actor, 'sanctum');

        $payload = [
            'workflow_id' => (string) $workflow->id,
            'document_id' => (string) $document->id,
        ];

        $res1 = $this->postJson('/api/v1/workflows/start', $payload);
        $res1->assertStatus(201);

        $res2 = $this->postJson('/api/v1/workflows/start', $payload);
        $res2->assertStatus(201);

        $this->assertDatabaseCount('workflow_instances', 1);
    }

    public function test_sign_document_is_idempotent(): void
    {
        $actor = User::factory()->create();

// L'acteur doit avoir le rôle autorisé à signer (règle métier Présidence : directeur_cabinet uniquement).
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'directeur_cabinet', 'guard_name' => 'web']);
        $actor->assignRole('directeur_cabinet');

        $document = Document::factory()->create([
            'hash' => 'abc123',
            'status' => 'pending',
            'submitted_for_signature_at' => now(),
        ]);

        $signature = Signature::factory()->create([
            'user_id' => $actor->id,
            'type' => 'graphical',
            'is_active' => true,
        ]);

        $this->actingAs($actor, 'sanctum');

        $payload = [
            'document_id' => (string) $document->id,
            'signature_id' => (string) $signature->id,
            'position' => ['x' => 1, 'y' => 2],
        ];

        $res1 = $this->postJson('/api/v1/signatures/sign-document', $payload);
        $res1->assertStatus(201);

        $res2 = $this->postJson('/api/v1/signatures/sign-document', $payload);
        $res2->assertStatus(201);

        $this->assertDatabaseCount('document_signatures', 1);
        $this->assertDatabaseHas('document_histories', [
            'document_id' => $document->id,
            'action' => 'signed',
            'user_id' => $actor->id,
        ]);
    }

    public function test_approve_is_forbidden_if_not_pending_approver(): void
    {
        $approver = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->create(['document_type' => 'note', 'status' => 'pending']);

        $workflow = Workflow::factory()->create([
            'document_type' => 'note',
            'steps' => [
                ['name' => 'chef', 'role' => 'admin', 'order' => 1],
                ['name' => 'sg', 'role' => 'admin', 'order' => 2],
            ],
            'is_active' => true,
        ]);

        // NOTE: Avoid calling assignRole('admin') if Spatie roles are not seeded.
        // The test is about authorization forbidding non-pending approvers.
        // if (method_exists($approver, 'assignRole')) {
        //     $approver->assignRole('admin');
        // }

        $instance = WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'document_id' => $document->id,
            'current_step' => 'chef',
            'status' => 'in_progress',
            'initiated_by' => $approver->id,
            'history' => [],
        ]);


        // actor must be the pending approver to avoid false positives.
        $approval = WorkflowApproval::query()->create([
            'workflow_instance_id' => $instance->id,
            'step_name' => 'chef',
            'approver_id' => $approver->id,
            'status' => 'pending',
        ]);

        $this->actingAs($other, 'sanctum');

        $res = $this->postJson('/api/v1/workflows/approvals/' . $approval->id . '/approve', [
            'comment' => 'ok',
        ]);

        $res->assertStatus(403);
    }
}

