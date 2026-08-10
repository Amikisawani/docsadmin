<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_merge_batches', function (Blueprint $table) {
            // En PostgreSQL, une colonne avec contrainte FK ne peut pas être modifiée.
            // La contrainte peut ne pas exister selon la façon dont la table a été créée :
            // on la supprime si présente (IF EXISTS), puis on rend template_id nullable.
            // SQLite ne supporte pas DROP CONSTRAINT (les migrations recréent la table).
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE mail_merge_batches DROP CONSTRAINT IF EXISTS mail_merge_batches_template_id_foreign');
            }

            // template_id devient nullable (la source peut être un document ou un fichier)
            $table->ulid('template_id')->nullable()->change();

            // Document finalisé (statut approved/signed) issu du workflow.
            $table->ulid('document_id')->nullable()->after('template_id');

            // Fichier source uploadé directement (docx/txt/pdf).
            $table->string('source_file_path')->nullable()->after('document_id');

            // Fichier des destinataires (xls/xlsx/txt/csv) uploadé.
            $table->string('recipients_file_path')->nullable()->after('source_file_path');

            // Variables globales pré-remplies (date_arrete, numero_arrete, nom_signataire...)
            $table->json('default_variables')->nullable()->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('mail_merge_batches', function (Blueprint $table) {
            $table->dropColumn(['document_id', 'source_file_path', 'recipients_file_path', 'default_variables']);
            $table->ulid('template_id')->nullable(false)->change();

            // Recréer la FK sur template_id (rétablit la contrainte d'origine)
            $table->foreign('template_id')->references('id')->on('templates')->cascadeOnDelete();
        });
    }
};
