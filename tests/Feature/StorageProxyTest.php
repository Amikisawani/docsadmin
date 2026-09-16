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

    public function test_pdf_preview_allows_same_origin_iframe(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('docs/notice.pdf', '%PDF-1.4 fake');

        $response = $this->get('/storage/docs/notice.pdf');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringStartsWith('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_html_shell_still_denies_clickjacking(): void
    {
        $this->get('/login')->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/up')->assertHeader('X-Frame-Options', 'DENY');
    }
}
