<?php

use Illuminate\Database\Migrations\Migration;

// NOTE:
// Cette migration dupliquée (departments) est volontairement neutralisée.
// Elle existait en double avec `2026_07_16_000001_create_departments_table.php`.
// Pour éviter les erreurs "table departments already exists" pendant les tests.

return new class extends Migration
{
    public function up(): void
    {
        // no-op (migration neutralisée)
    }

    public function down(): void
    {
        // no-op
    }
};
