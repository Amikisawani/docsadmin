<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageProxyTest extends TestCase
{
    public function test_it_streams_stored_files_under_storage_prefix(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('docs/hello.txt', 'bonjour');

        $response = $this->get('/storage/docs/hello.txt');
        $response->assertOk();
        $this->assertSame('bonjour', $response->streamedContent());
    }

    public function test_it_rejects_missing_or_traversal_paths(): void
    {
        Storage::fake('public');

        $this->get('/storage/missing.txt')->assertNotFound();
        $this->get('/storage/foo/../../secrets.txt')->assertNotFound();
    }
}
