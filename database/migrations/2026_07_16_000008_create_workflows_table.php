<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('document_type'); // Type de document concerné
            $table->json('steps'); // Étapes du workflow
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('workflow_id')->constrained()->cascadeOnDelete();
            $table->ulid('document_id')->constrained()->cascadeOnDelete();
            $table->string('current_step');
            $table->string('status')->default('in_progress'); // in_progress, completed, rejected, cancelled
            $table->json('history')->nullable();
            $table->ulid('initiated_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->string('step_name');
            $table->ulid('approver_id');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('comment')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflows');
    }
};