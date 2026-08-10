<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le suivi du circuit de signature du Directeur de Cabinet
 * sur les campagnes de publipostage.
 *
 *  - submitted_for_signature_at : date d'envoi à la signature (campagne pending_signature)
 *  - signed_at                   : date de signature par le Directeur
 *  - signed_by                   : identité du signataire (Directeur de Cabinet)
 *  - signature_id                : signature utilisée
 *  - signed_zip_path             : ZIP final signé (tous les PDF signés)
 *  - rejection_reason            : motif de rejet éventuel
 *
 * Le statut étendu de la campagne : completed → pending_signature → signed / rejected
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_merge_batches', function (Blueprint $table) {
            $table->timestamp('submitted_for_signature_at')->nullable()->after('completed_at');
            $table->timestamp('signed_at')->nullable()->after('submitted_for_signature_at');
            $table->ulid('signed_by')->nullable()->after('signed_at');
            $table->ulid('signature_id')->nullable()->after('signed_by');
            $table->string('signed_zip_path')->nullable()->after('signature_id');
            $table->text('rejection_reason')->nullable()->after('signed_zip_path');

            $table->index(['status', 'submitted_for_signature_at']);
        });
    }

    public function down(): void
    {
        Schema::table('mail_merge_batches', function (Blueprint $table) {
            $table->dropIndex(['status', 'submitted_for_signature_at']);
            $table->dropColumn([
                'submitted_for_signature_at',
                'signed_at',
                'signed_by',
                'signature_id',
                'signed_zip_path',
                'rejection_reason',
            ]);
        });
    }
};
