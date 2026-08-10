<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la distinction entre document unique et document de publipostage.
     *
     *  - flow_type : 'unique' (défaut, workflow obligatoire jusqu'à la signature)
     *                ou 'mail_merge' (destiné à être multiplié, visible dans le
     *                formulaire de publipostage qui a son propre workflow).
     *  - is_mail_merge : booléen dérivé pour faciliter les requêtes.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('flow_type')->default('unique')->after('status');
            $table->boolean('is_mail_merge')->default(false)->after('flow_type');
        });

        // Synchroniser les valeurs existantes : les documents sans flow_type restent 'unique'.
        \Illuminate\Support\Facades\DB::table('documents')->update([
            'flow_type' => 'unique',
            'is_mail_merge' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['flow_type', 'is_mail_merge']);
        });
    }
};

