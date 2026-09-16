<?php

namespace Tests\Feature;

use App\Application\Workflows\ApproveWorkflowUseCase;
use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowApproval;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MailMergeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }

    public function test_mail_merge_allows_workflow_with_different_document_type_for_source_document(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = Workflow::factory()->create([
            'name' => 'Validation publipostage générique',
            'document_type' => 'note',
            'steps' => [[
                'name' => 'chef',
                'role' => 'admin',
                'order' => 1,
            ]],
            'created_by' => $user->id,
        ]);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'conge',
            'subject' => 'Document source publipostage',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'content' => 'Bonjour {{prenom}}',
        ]);

        $response = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Campagne compatible',
            'document_id' => (string) $document->id,
            'workflow_id' => (string) $workflow->id,
            'format' => 'txt',
            'recipients' => [
                ['name' => 'Jean Dupont', 'variables' => ['prenom' => 'Jean']],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSame('awaiting_workflow', $response->json('data.status'));
    }

    public function test_mail_merge_creates_a_batch_and_starts_the_validation_workflow(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = Workflow::factory()->create([
            'name' => 'Validation publipostage',
            'document_type' => 'note',
            'steps' => [[
                'name' => 'chef',
                'role' => 'admin',
                'order' => 1,
            ]],
            'created_by' => $user->id,
        ]);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'note',
            'subject' => 'Convocation',
            'status' => 'approved',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'workflow_id' => $workflow->id,
            'content' => 'Convocation de {{prenom}} {{nom}} — Objet : {{objet}}',
        ]);

        $res = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Réunion du 15',
            'document_id' => (string) $document->id,
            'workflow_id' => (string) $workflow->id,
            'format' => 'txt',
            'recipients' => [
                [
                    'name' => 'Jean Dupont',
                    'variables' => ['prenom' => 'Jean', 'nom' => 'Dupont', 'objet' => 'Budget'],
                ],
                [
                    'name' => 'Marie Curie',
                    'variables' => ['prenom' => 'Marie', 'nom' => 'Curie', 'objet' => 'Science'],
                ],
            ],
        ]);

        $res->assertStatus(201);

        $batchId = $res->json('data.id');
        $this->assertNotNull($batchId);

        $this->assertDatabaseHas('mail_merge_batches', [
            'id' => $batchId,
            'status' => 'awaiting_workflow',
            'document_id' => $document->id,
            'workflow_id' => $workflow->id,
        ]);

        $this->assertNotNull($res->json('data.current_workflow_instance_id'));
        $this->assertDatabaseCount('mail_merge_recipients', 2);
    }

    public function test_mail_merge_batch_is_generated_when_workflow_is_approved(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = Workflow::factory()->create([
            'name' => 'Validation publipostage',
            'document_type' => 'note',
            'steps' => [[
                'name' => 'chef',
                'role' => 'admin',
                'order' => 1,
            ]],
            'created_by' => $user->id,
        ]);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'note',
            'subject' => 'Convocation',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'content' => 'Convocation de {{prenom}} {{nom}}',
        ]);

        $batchResponse = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Réunion du 15',
            'document_id' => (string) $document->id,
            'workflow_id' => (string) $workflow->id,
            'format' => 'txt',
            'recipients' => [
                ['name' => 'Jean Dupont', 'variables' => ['prenom' => 'Jean', 'nom' => 'Dupont']],
                ['name' => 'Marie Curie', 'variables' => ['prenom' => 'Marie', 'nom' => 'Curie']],
            ],
        ]);

        $batchResponse->assertStatus(201);
        $batchId = $batchResponse->json('data.id');
        $this->assertNotNull($batchId);

        $approval = WorkflowApproval::query()
            ->whereHas('workflowInstance', fn ($q) => $q->where('document_id', $document->id))
            ->firstOrFail();

        $approve = app(ApproveWorkflowUseCase::class)->execute([], $user, $approval->id);
        $this->assertSame('completed', $approve->status);

        $batch = \App\Domains\MailMerge\Models\MailMergeBatch::findOrFail($batchId);
        $this->assertSame('pending_signature', $batch->status);
        $this->assertSame(2, $batch->generated_count);
        $this->assertCount(2, $batch->recipients);

        foreach ($batch->recipients as $recipient) {
            $this->assertSame('generated', $recipient->status);
            Storage::disk('public')->assertExists($recipient->output_path);
        }
    }

    public function test_mail_merge_recipient_keeps_destinataire_and_generates_named_output(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = Workflow::factory()->create([
            'name' => 'Validation publipostage',
            'document_type' => 'note',
            'steps' => [[
                'name' => 'chef',
                'role' => 'admin',
                'order' => 1,
            ]],
            'created_by' => $user->id,
        ]);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'note',
            'subject' => 'Convocation',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'workflow_id' => $workflow->id,
            'content' => 'Convocation de {{prenom}} {{nom}}',
        ]);

        $response = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Réunion du 15',
            'document_id' => (string) $document->id,
            'workflow_id' => (string) $workflow->id,
            'format' => 'txt',
            'recipients' => [
                [
                    'name' => 'Jean Dupont',
                    'destinataire' => 'Jean Dupont',
                    'variables' => ['prenom' => 'Jean', 'nom' => 'Dupont'],
                ],
            ],
        ]);

        $response->assertStatus(201);

        $batchId = $response->json('data.id');
        $approval = WorkflowApproval::query()
            ->whereHas('workflowInstance', fn ($q) => $q->where('document_id', $document->id))
            ->firstOrFail();

        $approve = app(ApproveWorkflowUseCase::class)->execute([], $user, $approval->id);
        $this->assertSame('completed', $approve->status);

        $batch = \App\Domains\MailMerge\Models\MailMergeBatch::findOrFail($batchId);
        $recipient = $batch->recipients()->firstOrFail();

        $this->assertSame('Jean Dupont', $recipient->destinataire);
        $this->assertNotNull($recipient->output_path);
        $this->assertStringContainsString('jean-dupont', basename($recipient->output_path));
        Storage::disk('public')->assertExists($recipient->output_path);
    }

    public function test_mail_merge_documents_endpoint_returns_created_mail_merge_sources_even_when_draft(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'note',
            'subject' => 'Document source publipostage',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'content' => 'Note pour {{nom}}',
        ]);

        $res = $this->getJson('/api/v1/mail-merge/documents');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertSame($document->id, $res->json('data.0.id'));
    }

    public function test_mail_merge_returns_real_failure_reason_when_generation_fails(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = Workflow::factory()->create([
            'name' => 'Validation publipostage',
            'document_type' => 'note',
            'steps' => [[
                'name' => 'chef',
                'role' => 'admin',
                'order' => 1,
            ]],
            'created_by' => $user->id,
        ]);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'note',
            'subject' => 'Document source vide',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'workflow_id' => $workflow->id,
            'content' => null,
        ]);

        $createResponse = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Campagne invalide',
            'document_id' => (string) $document->id,
            'workflow_id' => (string) $workflow->id,
            'format' => 'txt',
            'recipients' => [
                ['name' => 'Jean Dupont', 'variables' => ['prenom' => 'Jean', 'nom' => 'Dupont']],
            ],
        ]);

        $createResponse->assertStatus(201);

        $batchId = $createResponse->json('data.id');
        $approval = WorkflowApproval::query()
            ->whereHas('workflowInstance', fn ($q) => $q->where('document_id', $document->id))
            ->firstOrFail();

        app(ApproveWorkflowUseCase::class)->execute([], $user, $approval->id);

        $batch = \App\Domains\MailMerge\Models\MailMergeBatch::findOrFail($batchId);
        $this->assertSame('failed', $batch->status);
        $this->assertStringContainsString(
            'ne contient aucun contenu exploitable',
            implode(' ', (array) ($batch->errors ?? []))
        );
    }

    public function test_mail_merge_index_lists_only_own_batches(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = Workflow::factory()->create([
            'name' => 'Validation publipostage',
            'document_type' => 'note',
            'steps' => [[
                'name' => 'chef',
                'role' => 'admin',
                'order' => 1,
            ]],
            'created_by' => $user->id,
        ]);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'note',
            'subject' => 'Document source',
            'status' => 'approved',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'workflow_id' => $workflow->id,
            'content' => 'Note pour {{nom}}',
        ]);

        $this->postJson('/api/v1/mail-merge', [
            'title' => 'Ma campagne',
            'document_id' => (string) $document->id,
            'workflow_id' => (string) $workflow->id,
            'format' => 'txt',
            'recipients' => [['name' => 'A', 'variables' => ['nom' => 'Alpha']]],
        ])->assertStatus(201);

        // L'autre utilisateur ne voit que ses propres campagnes (aucune).
        $this->actingAs($other, 'sanctum');
        $res = $this->getJson('/api/v1/mail-merge');
        $res->assertOk();
        $this->assertCount(0, $res->json('data.data'));
    }

    public function test_notification_arrete_sample_replaces_mail_merge_variables(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $content = file_get_contents(base_path('resources/samples/mailmerge/notification-arrete.txt'));
        $this->assertNotFalse($content);
        $this->assertStringContainsString('{{matricule}}', $content);
        $this->assertStringContainsString('{{numero_arrete}}', $content);

        $document = Document::factory()->create([
            'author_id' => $user->id,
            'document_type' => 'arrete',
            'subject' => 'Notification d\'arrêté — modèle publipostage',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'content' => $content,
        ]);

        $response = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Notification d\'arrêté — jeu de test',
            'document_id' => (string) $document->id,
            'format' => 'txt',
            'default_variables' => [
                'reference' => 'PR/SG/TEST/001/2026',
                'numero_arrete' => '019/CAB.VPMIN/FP-MA-ISP/JPL/2026',
                'date_arrete' => '06/02/2026',
                'nom_signataire' => 'Jean Jacques LUBOYA TSHISHIMA',
                'fonction_signataire' => 'Secrétaire Général auprès du Président de la République',
            ],
            'recipients' => [[
                'name' => 'Jean MUKENDI KABONGO',
                'variables' => [
                    'civilite' => 'Monsieur',
                    'nom_complet' => 'Jean MUKENDI KABONGO',
                    'matricule' => 'TEST-001',
                    'grade' => 'ATA 2',
                    'fonction' => 'Agent de Carrière des Services Publics de l\'Etat',
                    'adresse_administration' => 'C/o Palais de la Nation',
                    'ville' => 'Kinshasa / Gombe',
                ],
            ]],
        ]);

        $response->assertStatus(201);
        $batch = \App\Domains\MailMerge\Models\MailMergeBatch::findOrFail($response->json('data.id'));
        $recipient = $batch->recipients()->firstOrFail();
        $this->assertNotNull($recipient->output_path);
        Storage::disk('public')->assertExists($recipient->output_path);

        $generated = (string) Storage::disk('public')->get($recipient->output_path);
        $this->assertStringContainsString('Jean MUKENDI KABONGO', $generated);
        $this->assertStringContainsString('TEST-001', $generated);
        $this->assertStringContainsString('019/CAB.VPMIN/FP-MA-ISP/JPL/2026', $generated);
        $this->assertStringNotContainsString('{{matricule}}', $generated);
        $this->assertStringNotContainsString('{{nom_complet}}', $generated);
        $this->assertStringNotContainsString('{{numero_arrete}}', $generated);
    }

    public function test_director_can_sign_campaign_when_source_is_a_txt_file(): void
    {
        Storage::fake('public');
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleAndPermissionSeeder'])->run();

        $author = User::factory()->create();
        $director = User::factory()->create();
        $director->assignRole('directeur_cabinet');

        Storage::disk('public')->put(
            'sources/notification.txt',
            'Notification à {{nom_complet}}, matricule {{matricule}}.'
        );

        $document = Document::factory()->create([
            'author_id' => $author->id,
            'document_type' => 'notification',
            'subject' => 'Notification d\'arrêté',
            'status' => 'draft',
            'flow_type' => 'mail_merge',
            'is_mail_merge' => true,
            'content' => '',
            'source_file_path' => 'sources/notification.txt',
        ]);

        $this->actingAs($author, 'sanctum');
        $created = $this->postJson('/api/v1/mail-merge', [
            'title' => 'Campagne signature TXT',
            'document_id' => (string) $document->id,
            'format' => 'pdf',
            'recipients' => [[
                'name' => 'Jean Test',
                'variables' => [
                    'nom_complet' => 'Jean Test',
                    'matricule' => 'T-001',
                ],
            ]],
        ]);

        $created->assertStatus(201);
        $this->assertSame('pending_signature', $created->json('data.status'));
        $batchId = $created->json('data.id');

        $signature = Signature::query()->create([
            'user_id' => $director->id,
            'type' => 'digital',
            'label' => 'Paraphe cabinet',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($director, 'sanctum');
        $signed = $this->postJson('/api/v1/mail-merge/'.$batchId.'/sign-campaign', [
            'signature_id' => $signature->id,
            'position' => ['x' => 50, 'y' => 80],
        ]);

        $signed->assertCreated();
        $this->assertSame('signed', $signed->json('data.status'));
        $this->assertNotNull($signed->json('data.signed_at'));

        $recipient = \App\Domains\MailMerge\Models\MailMergeBatch::findOrFail($batchId)->recipients()->firstOrFail();
        $this->assertSame('signed', $recipient->status);
        Storage::disk('public')->assertExists($recipient->output_path);
    }
}

