<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Accès au disque applicatif (local en dev, S3/R2 sur Laravel Cloud).
 */
final class StoredFile
{
    public static function disk(): Filesystem
    {
        return Storage::disk('public');
    }

    public static function isRemote(): bool
    {
        return config('filesystems.disks.public.driver') === 's3';
    }

    /**
     * Exécute un callback avec un chemin de fichier local.
     * Copie vers /tmp si le disque n'est pas local (Cloud Object Storage).
     */
    public static function withLocal(string $path, callable $callback): mixed
    {
        $disk = self::disk();

        if (! self::isRemote()) {
            return $callback($disk->path($path));
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $tmp = tempnam(sys_get_temp_dir(), 'af_');
        if ($ext !== '') {
            $named = $tmp.'.'.$ext;
            @unlink($tmp);
            $tmp = $named;
        }

        $stream = $disk->readStream($path);
        if ($stream === false) {
            throw new \RuntimeException("Impossible de lire le fichier stocké : {$path}");
        }

        $out = fopen($tmp, 'w');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        if (is_resource($stream)) {
            fclose($stream);
        }

        try {
            return $callback($tmp);
        } finally {
            @unlink($tmp);
        }
    }
}
