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
            // Acceptation explicite CGU / CGV / Politique de confidentialité
            $table->timestamp('cgu_accepted_at')->nullable()->after('tracking_consent');
            $table->timestamp('cgv_accepted_at')->nullable()->after('cgu_accepted_at');
            $table->timestamp('confidentialite_accepted_at')->nullable()->after('cgv_accepted_at');

            // Préférences de notifications push (toutes activées par défaut)
            $table->boolean('notifications_reservations')->default(true)->after('confidentialite_accepted_at');
            $table->boolean('notifications_paiements')->default(true)->after('notifications_reservations');
            $table->boolean('notifications_messages')->default(true)->after('notifications_paiements');
            $table->boolean('notifications_rappels')->default(true)->after('notifications_messages');
            $table->boolean('notifications_promotions')->default(true)->after('notifications_rappels');
            $table->boolean('notifications_mises_a_jour')->default(true)->after('notifications_promotions');

            // Dismiss de la bannière push sur le dashboard
            $table->timestamp('push_banner_dismissed_at')->nullable()->after('notifications_mises_a_jour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cgu_accepted_at',
                'cgv_accepted_at',
                'confidentialite_accepted_at',
                'notifications_reservations',
                'notifications_paiements',
                'notifications_messages',
                'notifications_rappels',
                'notifications_promotions',
                'notifications_mises_a_jour',
                'push_banner_dismissed_at',
            ]);
        });
    }
};
