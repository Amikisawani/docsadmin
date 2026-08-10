<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('submitted_for_signature_at')->nullable()->after('signed_pdf_path');
            $table->string('priority')->default('normale')->after('submitted_for_signature_at');
            $table->date('deadline')->nullable()->after('priority');
            $table->text('rejection_reason')->nullable()->after('deadline');
            $table->timestamp('recalled_at')->nullable()->after('rejection_reason');

            $table->index(['status', 'submitted_for_signature_at']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['status', 'submitted_for_signature_at']);
            $table->dropColumn([
                'submitted_for_signature_at',
                'priority',
                'deadline',
                'rejection_reason',
                'recalled_at',
            ]);
        });
    }
};
