<?php

namespace Tests\Feature;

use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;

use Tests\TestCase;

class DocumentQrVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--env' => 'testing', '--database' => 'sqlite'])->run();
    }

    public function test_public_verification_endpoint_returns_is_archived_after_archiving(): void
    {
        $actor = User::factory()->create();

        $document = Document::factory()->create([
            'document_type' => 'note',
            'status' => 'draft',
            'is_archived' => false,
            'is_deleted' => false,
            'reference' => 'DOC-QR-REF-1',
        ]);


        $this->actingAs($actor, 'sanctum');



        $payload = [

            'archive_box_id' => null,
            'reference' => $document->reference,
            'category' => $document->document_type,
            'conservation_duration' => 'ans',
            'conservation_until_days' => 30,
            'notes' => 'archive test',
        ];

        $res = $this->postJson('/api/v1/documents/' . $document->id . '/archive', $payload);
        $res->assertStatus(201);

        $verif = $this->getJson('/public/documents/verify?hash=' . urlencode((string) $document->hash));
        $verif->assertStatus(200);
        $verif->assertJsonPath('data.is_archived', true);
        $verif->assertJsonPath('data.qr_anchor', hash('sha256', 'AdminFlow|qr|' . (string) $document->hash));

    }

    public function test_public_verification_endpoint_rejects_unknown_hash(): void
    {
        $verif = $this->getJson('/public/documents/verify?hash=unknown-hash');
        $verif->assertStatus(404);
        $verif->assertJsonPath('is_valid', false);
    }
}

