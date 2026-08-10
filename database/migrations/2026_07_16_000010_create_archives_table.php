<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_boxes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable(); // Emplacement physique ou logique
            $table->integer('capacity')->nullable(); // Capacité maximale
            $table->string('status')->default('active'); // active, full, archived
            $table->ulid('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('archives', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('document_id')->constrained()->cascadeOnDelete();
            $table->ulid('archive_box_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('category')->nullable();
            $table->string('conservation_duration')->nullable(); // D1, D2, D3, D4, permanent
            $table->timestamp('archived_at');
            $table->timestamp('conservation_until')->nullable();
            $table->string('status')->default('active'); // active, destroyed, transferred
            $table->text('notes')->nullable();
            $table->ulid('archived_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('document_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->integer('size');
            $table->string('hash');
            $table->string('type')->default('attachment'); // attachment, annex, appendice
            $table->ulid('uploaded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
        Schema::dropIfExists('archives');
        Schema::dropIfExists('archive_boxes');
    }
};