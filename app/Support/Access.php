<?php

namespace App\Support;

use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;

final class Access
{
    public static function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public static function isDirectorCabinet(User $user): bool
    {
        return $user->hasRole('directeur_cabinet');
    }

    public static function canManageUsers(User $user): bool
    {
        return self::isAdmin($user);
    }

    public static function canManageDepartments(User $user): bool
    {
        return self::isAdmin($user);
    }

    public static function canManageWorkflows(User $user): bool
    {
        return self::isAdmin($user);
    }

    public static function canReadAudit(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('auditeur');
    }

    public static function canViewGlobalStats(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'auditeur', 'directeur', 'secretaire_general']);
    }

    public static function canArchive(User $user, Document $document): bool
    {
        if ($user->hasRole('archiviste') || self::isAdmin($user)) {
            return true;
        }

        return self::isAuthor($user, $document);
    }

    public static function isAuthor(User $user, Document $document): bool
    {
        return (string) $document->author_id === (string) $user->id;
    }

    public static function canViewDocument(User $user, Document $document): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if (self::isAuthor($user, $document)) {
            return true;
        }

        // Le Directeur de Cabinet ne voit que les documents envoyés à la signature.
        if (self::isDirectorCabinet($user) && $document->submitted_for_signature_at) {
            return true;
        }

        return false;
    }

    public static function canModifyDocument(User $user, Document $document): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        return self::isAuthor($user, $document);
    }

    public static function canManageSignature(User $user, Signature $signature): bool
    {
        return (string) $signature->user_id === (string) $user->id || self::isAdmin($user);
    }

    public static function ensureCanViewDocument(User $user, Document $document): void
    {
        abort_unless(self::canViewDocument($user, $document), 403, 'Vous n\'êtes pas autorisé à consulter ce document.');
    }

    public static function ensureCanModifyDocument(User $user, Document $document): void
    {
        abort_unless(self::canModifyDocument($user, $document), 403, 'Vous n\'êtes pas autorisé à modifier ce document.');
    }

    public static function ensureCanArchive(User $user, Document $document): void
    {
        abort_unless(self::canArchive($user, $document), 403, 'Vous n\'êtes pas autorisé à archiver ce document.');
    }

    public static function ensureAdmin(User $user): void
    {
        abort_unless(self::isAdmin($user), 403, 'Accès réservé aux administrateurs.');
    }

    public static function ensureCanManageUsers(User $user): void
    {
        abort_unless(self::canManageUsers($user), 403, 'Accès réservé aux administrateurs.');
    }

    public static function ensureCanManageDepartments(User $user): void
    {
        abort_unless(self::canManageDepartments($user), 403, 'Accès réservé aux administrateurs.');
    }

    public static function ensureCanManageWorkflows(User $user): void
    {
        abort_unless(self::canManageWorkflows($user), 403, 'Accès réservé aux administrateurs.');
    }

    public static function ensureCanReadAudit(User $user): void
    {
        abort_unless(self::canReadAudit($user), 403, 'Accès au journal d\'audit réservé aux administrateurs et auditeurs.');
    }

    public static function ensureCanManageSignature(User $user, Signature $signature): void
    {
        abort_unless(self::canManageSignature($user, $signature), 403, 'Cette signature ne vous appartient pas.');
    }

    public static function sanitizeSort(?string $sort, array $allowed, string $default): string
    {
        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    public static function sanitizeOrder(?string $order): string
    {
        return strtolower((string) $order) === 'asc' ? 'asc' : 'desc';
    }

    public static function perPage(mixed $value, int $default = 15, int $max = 100): int
    {
        $n = (int) ($value ?? $default);
        if ($n < 1) {
            return $default;
        }

        return min($n, $max);
    }
}
