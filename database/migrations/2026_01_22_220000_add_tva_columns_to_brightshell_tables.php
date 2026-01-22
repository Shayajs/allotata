<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les colonnes TVA aux devis si elles n'existent pas
        if (!Schema::hasColumn('brightshell_devis', 'mode_tva')) {
            Schema::table('brightshell_devis', function (Blueprint $table) {
                $table->string('mode_tva')->default('non_assujetti')->after('montant_ht');
                $table->decimal('taux_tva', 5, 2)->default(20)->after('mode_tva');
                $table->decimal('montant_tva', 10, 2)->default(0)->after('taux_tva');
                $table->decimal('montant_total', 10, 2)->nullable()->after('montant_tva');
            });
        }

        // Ajouter les colonnes TVA aux factures si elles n'existent pas
        if (!Schema::hasColumn('brightshell_factures', 'mode_tva')) {
            Schema::table('brightshell_factures', function (Blueprint $table) {
                $table->string('mode_tva')->default('non_assujetti')->after('montant_total');
                $table->decimal('taux_tva', 5, 2)->default(20)->after('mode_tva');
                $table->decimal('montant_tva', 10, 2)->default(0)->after('taux_tva');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('brightshell_devis', 'mode_tva')) {
            Schema::table('brightshell_devis', function (Blueprint $table) {
                $table->dropColumn(['mode_tva', 'taux_tva', 'montant_tva', 'montant_total']);
            });
        }

        if (Schema::hasColumn('brightshell_factures', 'mode_tva')) {
            Schema::table('brightshell_factures', function (Blueprint $table) {
                $table->dropColumn(['mode_tva', 'taux_tva', 'montant_tva']);
            });
        }
    }
};
