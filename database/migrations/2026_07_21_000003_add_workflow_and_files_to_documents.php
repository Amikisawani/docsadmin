<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Workflow choisi à la création (nullable : rétro-compatibilité)
            $table->ulid('workflow_id')->nullable()->after('department_id');
            $table->foreign('workflow_id')->references('id')->on('workflows')->nullOnDelete();

            // Instance de workflow en cours
            $table->ulid('current_workflow_instance_id')->nullable()->after('workflow_id');

            // Fichier source Word (.docx) rédigé par l'agent
            $table->string('source_file_path')->nullable()->after('content');

            // PDF final signé et verrouillé
            $table->string('signed_pdf_path')->nullable()->after('source_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropColumn([
                'workflow_id',
                'current_workflow_instance_id',
                'source_file_path',
                'signed_pdf_path',
            ]);
        });
    }
};

