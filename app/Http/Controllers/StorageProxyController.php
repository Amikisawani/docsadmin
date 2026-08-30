<?php

namespace App\Http\Controllers;

use App\Support\StoredFile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert les fichiers du disque applicatif sous /storage/{path}.
 * Sur Laravel Cloud le symlink public/storage n'existe pas (FS éphémère).
 */
class StorageProxyController extends Controller
{
    public function show(Request $request, string $path): Response
    {
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = StoredFile::disk();

        if (! $disk->exists($path)) {
            abort(404);
        }

        $download = $request->boolean('download');

        return $download
            ? $disk->download($path)
            : $disk->response($path);
    }
}
