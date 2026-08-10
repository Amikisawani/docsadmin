<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_merge_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->ulid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable(); // Titre de la campagne
            $table->string('status')->default('processing'); // processing, completed, partial, failed
            $table->integer('total_recipients')->default(0);
            $table->integer('generated_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->string('zip_path')->nullable();       // ZIP contenant tous les PDF
            $table->string('format')->default('pdf');     // pdf, docx, txt
            $table->json('errors')->nullable();           // Détail des erreurs éventuelles
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_merge_recipients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('batch_id')->constrained('mail_merge_batches')->cascadeOnDelete();
            $table->string('name');                                    // Nom complet du destinataire
            $table->json('variables')->nullable();                     // Variables de fusion du destinataire
            $table->string('output_path')->nullable();                 // Chemin du fichier généré (PDF)
            $table->string('status')->default('pending');              // pending, generated, failed
            $table->string('error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_merge_recipients');
        Schema::dropIfExists('mail_merge_batches');
    }
};

