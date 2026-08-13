<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->json('snapshot')->nullable()->after('notes');
            $table->date('date_paiement')->nullable()->after('date_echeance');
            $table->timestamp('verrouillee_at')->nullable()->after('snapshot');
        });

        try {
            Schema::table('factures', function (Blueprint $table) {
                $table->dropUnique(['numero_facture']);
            });
        } catch (\Throwable $e) {
            // Index déjà retiré ou nommé autrement
        }

        Schema::table('factures', function (Blueprint $table) {
            $table->unique(['entreprise_id', 'numero_facture']);
        });

        Schema::table('devis', function (Blueprint $table) {
            $table->string('numero_devis')->nullable()->after('id');
            $table->json('snapshot')->nullable()->after('notes_prestataire');
            $table->date('date_validite')->nullable()->after('snapshot');
            $table->timestamp('verrouille_at')->nullable()->after('date_validite');
            $table->unique(['entreprise_id', 'numero_devis']);
        });
    }

    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropUnique(['entreprise_id', 'numero_devis']);
            $table->dropColumn(['numero_devis', 'snapshot', 'date_validite', 'verrouille_at']);
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropUnique(['entreprise_id', 'numero_facture']);
            $table->unique('numero_facture');
            $table->dropColumn(['snapshot', 'date_paiement', 'verrouillee_at']);
        });
    }
};
