<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            if (!Schema::hasColumn('echeances', 'entreprise_id')) {
                $table->foreignId('entreprise_id')->nullable()->after('user_id')->constrained('entreprises')->onDelete('cascade');
            }
            if (!Schema::hasColumn('echeances', 'subscription_type')) {
                $table->string('subscription_type', 32)->default('default')->after('entreprise_id');
            }
            if (!Schema::hasColumn('echeances', 'reduction_manuel')) {
                $table->decimal('reduction_manuel', 10, 2)->default(0)->after('reduction_promo');
            }
            if (!Schema::hasColumn('echeances', 'reduction_manuel_notes')) {
                $table->text('reduction_manuel_notes')->nullable()->after('reduction_manuel');
            }
        });

        Schema::table('echeances', function (Blueprint $table) {
            $table->index(['user_id', 'entreprise_id', 'subscription_type', 'periode_debut', 'periode_fin'], 'echeances_scope_periode_index');
        });
    }

    public function down(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            $table->dropIndex('echeances_scope_periode_index');
        });
        Schema::table('echeances', function (Blueprint $table) {
            if (Schema::hasColumn('echeances', 'reduction_manuel_notes')) {
                $table->dropColumn('reduction_manuel_notes');
            }
            if (Schema::hasColumn('echeances', 'reduction_manuel')) {
                $table->dropColumn('reduction_manuel');
            }
            if (Schema::hasColumn('echeances', 'subscription_type')) {
                $table->dropColumn('subscription_type');
            }
            if (Schema::hasColumn('echeances', 'entreprise_id')) {
                $table->dropConstrainedForeignId('entreprise_id');
            }
        });
    }
};
