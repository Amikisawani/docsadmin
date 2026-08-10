<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('document_number')->unique();
            $table->string('reference')->nullable();
            $table->string('subject');
            $table->string('document_type');
            $table->ulid('author_id');
            $table->ulid('department_id')->nullable();
            $table->string('version')->default('1.0');
            $table->string('status')->default('draft');
            $table->string('confidentiality')->default('interne');
            $table->date('document_date');
            $table->longText('content')->nullable();
            $table->string('hash')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            $table->index(['status', 'is_archived', 'is_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
