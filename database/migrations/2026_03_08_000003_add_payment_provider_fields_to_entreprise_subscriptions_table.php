<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprise_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('entreprise_subscriptions', 'payment_provider')) {
                $table->string('payment_provider', 32)
                    ->nullable()
                    ->after('stripe_price')
                    ->index();
            }
            if (!Schema::hasColumn('entreprise_subscriptions', 'provider_subscription_id')) {
                $table->string('provider_subscription_id')
                    ->nullable()
                    ->after('payment_provider');
            }
            if (!Schema::hasColumn('entreprise_subscriptions', 'provider_payload')) {
                $table->json('provider_payload')
                    ->nullable()
                    ->after('provider_subscription_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('entreprise_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('entreprise_subscriptions', 'provider_payload')) {
                $table->dropColumn('provider_payload');
            }
            if (Schema::hasColumn('entreprise_subscriptions', 'provider_subscription_id')) {
                $table->dropColumn('provider_subscription_id');
            }
            if (Schema::hasColumn('entreprise_subscriptions', 'payment_provider')) {
                $table->dropColumn('payment_provider');
            }
        });
    }
};
