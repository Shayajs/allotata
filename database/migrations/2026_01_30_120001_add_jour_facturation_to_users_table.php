<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'jour_facturation')) {
                $table->unsignedTinyInteger('jour_facturation')->nullable()->after('abonnement_manuel_jour_renouvellement');
            }
        });

        // Remplir depuis abonnement_manuel_jour_renouvellement si présent, sinon 1
        DB::statement('
            UPDATE users
            SET jour_facturation = COALESCE(abonnement_manuel_jour_renouvellement, 1)
            WHERE jour_facturation IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'jour_facturation')) {
                $table->dropColumn('jour_facturation');
            }
        });
    }
};
