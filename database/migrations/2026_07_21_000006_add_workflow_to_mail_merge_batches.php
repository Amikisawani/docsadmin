<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le publipostage a désormais son propre workflow de validation jusqu'à la signature.
     *
     *  - workflow_id : workflow choisi à la création du batch (validation puis signature).
     *  - current_workflow_instance_id : instance en cours pour suivre la progression.
     *  - status étendu : 'awaiting_workflow' puis les statuts usuels du workflow.
     */
    public function up(): void
    {
        Schema::table('mail_merge_batches', function (Blueprint $table) {
            $table->ulid('workflow_id')->nullable()->after('document_id');
            $table->ulid('current_workflow_instance_id')->nullable()->after('workflow_id');
        });
    }

    public function down(): void
    {
        Schema::table('mail_merge_batches', function (Blueprint $table) {
            $table->dropColumn(['workflow_id', 'current_workflow_instance_id']);
        });
    }
};

