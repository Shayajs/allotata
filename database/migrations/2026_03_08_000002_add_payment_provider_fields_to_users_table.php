<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'payment_provider')) {
                $table->string('payment_provider', 32)
                    ->nullable()
                    ->after('stripe_payment_method_id')
                    ->index();
            }
            if (!Schema::hasColumn('users', 'provider_customer_id')) {
                $table->string('provider_customer_id')
                    ->nullable()
                    ->after('payment_provider');
            }
            if (!Schema::hasColumn('users', 'provider_payment_method_id')) {
                $table->string('provider_payment_method_id')
                    ->nullable()
                    ->after('provider_customer_id');
            }
            if (!Schema::hasColumn('users', 'provider_payload')) {
                $table->json('provider_payload')
                    ->nullable()
                    ->after('provider_payment_method_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'provider_payload')) {
                $table->dropColumn('provider_payload');
            }
            if (Schema::hasColumn('users', 'provider_payment_method_id')) {
                $table->dropColumn('provider_payment_method_id');
            }
            if (Schema::hasColumn('users', 'provider_customer_id')) {
                $table->dropColumn('provider_customer_id');
            }
            if (Schema::hasColumn('users', 'payment_provider')) {
                $table->dropColumn('payment_provider');
            }
        });
    }
};
