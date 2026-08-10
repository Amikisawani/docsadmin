<?php

namespace Tests\Feature;

use App\Domains\Archives\Models\Archive;
use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use Tests\TestCase;

class ArchiveDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }

    public function test_archive_document_creates_archive_and_sets_is_archived(): void
    {
        $actor = User::factory()->create();
        $document = Document::factory()->create([
            'document_type' => 'note',
            'status' => 'draft',
            'is_archived' => false,
            'reference' => 'DOC-REF-TEST-001',
        ]);

        $this->actingAs($actor, 'sanctum');

        $payload = [
            'archive_box_id' => null,
            'reference' => 'REF-TEST-001',

            'category' => 'note',
            'conservation_duration' => 'ans',
            'conservation_until_days' => 30,
            'notes' => 'archive test',
        ];

        $res = $this->postJson('/api/v1/documents/'.$document->id.'/archive', $payload);
        $res->assertStatus(201);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'is_archived' => 1,
        ]);

        $this->assertDatabaseHas('archives', [
            'document_id' => $document->id,
        ]);

        $archive = Archive::query()->where('document_id', $document->id)->first();
        $this->assertNotNull($archive);

        $verif = $this->getJson('/public/documents/verify?hash='.urlencode($document->hash));
        $verif->assertStatus(200);
        $verif->assertJsonPath('data.is_archived', true);
    }
}
