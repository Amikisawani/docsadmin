<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // graphical, digital, certificate
            $table->string('label')->nullable();
            $table->string('image_path')->nullable(); // Signature graphique PNG
            $table->text('certificate_data')->nullable(); // Certificat numérique (encodé)
            $table->string('certificate_serial')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->string('hash_algorithm')->default('sha256');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Position, rotation, dimensions
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_signatures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('document_id')->constrained()->cascadeOnDelete();
            $table->ulid('signature_id')->constrained()->cascadeOnDelete();
            $table->ulid('signed_by');
            $table->string('type'); // graphical, digital
            $table->text('hash_signature')->nullable(); // Empreinte cryptographique
            $table->text('certificate_chain')->nullable();
            $table->timestamp('signed_at');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('position')->nullable(); // Position sur le document
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('signatures');
    }
};
