<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'abonnement_manuel')) {
                $table->boolean('abonnement_manuel')->default(false)->after('trial_ends_at');
            }
            if (!Schema::hasColumn('users', 'abonnement_manuel_actif_jusqu')) {
                $table->date('abonnement_manuel_actif_jusqu')->nullable()->after('abonnement_manuel');
            }
            if (!Schema::hasColumn('users', 'abonnement_manuel_notes')) {
                $table->text('abonnement_manuel_notes')->nullable()->after('abonnement_manuel_actif_jusqu');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'abonnement_manuel')) {
                $table->dropColumn('abonnement_manuel');
            }
            if (Schema::hasColumn('users', 'abonnement_manuel_actif_jusqu')) {
                $table->dropColumn('abonnement_manuel_actif_jusqu');
            }
            if (Schema::hasColumn('users', 'abonnement_manuel_notes')) {
                $table->dropColumn('abonnement_manuel_notes');
            }
        });
    }
};
