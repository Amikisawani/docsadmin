<?php

namespace Tests\Feature;

use App\Domains\Templates\Models\Template;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }

    public function test_template_can_be_created_with_content_mode(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $res = $this->postJson('/api/v1/templates', [
            'name' => 'Note de service',
            'type' => 'text',
            'category' => 'note',
            'description' => 'Template note',
            'content' => "Bonjour {{nom}} {{prenom}},\n\nVotre {{objet}} est traite.\n\nLe directeur",
        ]);

        $res->assertStatus(201);
        $res->assertJsonPath('data.name', 'Note de service');

        $this->assertDatabaseHas('templates', [
            'name' => 'Note de service',
            'type' => 'text',
            'content' => "Bonjour {{nom}} {{prenom}},\n\nVotre {{objet}} est traite.\n\nLe directeur",
        ]);

        $template = Template::where('name', 'Note de service')->first();
        $this->assertNotNull($template);
        $this->assertNotNull($template->file_path);
        $this->assertStringEndsWith('.txt', $template->file_path);
        Storage::disk('public')->assertExists($template->file_path);
    }

    public function test_template_can_be_created_with_file_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->create('modele.docx', 1024, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $res = $this->postJson('/api/v1/templates', [
            'name' => 'Modele Word',
            'type' => 'word',
            'category' => 'courrier',
            'description' => 'Import docx',
            'file' => $file,
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('templates', ['name' => 'Modele Word', 'type' => 'word']);

        $template = Template::where('name', 'Modele Word')->first();
        $this->assertNotNull($template);
        $this->assertNotNull($template->file_path);
        Storage::disk('public')->assertExists($template->file_path);
    }

    public function test_template_content_mode_requires_content(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $res = $this->postJson('/api/v1/templates', [
            'name' => 'Sans contenu',
            'type' => 'text',
            'category' => 'note',
        ]);

        $res->assertStatus(422);
    }

    public function test_generate_pdf_from_content_template(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $template = Template::create([
            'name' => 'Arrete',
            'type' => 'text',
            'category' => 'arrete',
            'description' => 'Template arrete',
            'content' => "ARRETE N° {{numero}}\nObjet : {{objet}}\nSignataire : {{signataire}}",
            'file_path' => 'templates/arrete-' . uniqid() . '.txt',
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $res = $this->postJson('/api/v1/templates/' . $template->id . '/generate', [
            'variables' => [
                'numero' => '2026/001',
                'objet' => 'Nomination',
                'signataire' => 'M. le Directeur',
            ],
            'format' => 'pdf',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('data.format', 'pdf');

        $path = $res->json('data.path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        // Le PDF généré doit contenir les variables remplacées (extrait texte).
        $content = Storage::disk('public')->get($path);
        $this->assertStringNotContainsString('{{numero}}', $content);
    }

    public function test_generate_txt_with_variable_substitution(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $template = Template::create([
            'name' => 'Courrier',
            'type' => 'text',
            'category' => 'courrier',
            'description' => '',
            'content' => 'Objet : {{objet}}',
            'file_path' => 'templates/courrier-' . uniqid() . '.txt',
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $res = $this->postJson('/api/v1/templates/' . $template->id . '/generate', [
            'variables' => ['objet' => 'Demande de congé'],
            'format' => 'txt',
        ]);

        $res->assertStatus(200);
        $path = $res->json('data.path');
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('Demande de congé', Storage::disk('public')->get($path));
    }
}

