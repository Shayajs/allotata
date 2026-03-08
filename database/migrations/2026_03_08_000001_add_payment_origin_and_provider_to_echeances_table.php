<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            if (!Schema::hasColumn('echeances', 'payment_origin')) {
                $table->string('payment_origin', 32)
                    ->default('auto_card')
                    ->after('subscription_type')
                    ->index();
            }
            if (!Schema::hasColumn('echeances', 'payment_provider')) {
                $table->string('payment_provider', 32)
                    ->nullable()
                    ->after('payment_origin')
                    ->index();
            }
            if (!Schema::hasColumn('echeances', 'auto_charge_eligible')) {
                $table->boolean('auto_charge_eligible')
                    ->default(true)
                    ->after('payment_provider')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            if (Schema::hasColumn('echeances', 'auto_charge_eligible')) {
                $table->dropColumn('auto_charge_eligible');
            }
            if (Schema::hasColumn('echeances', 'payment_provider')) {
                $table->dropColumn('payment_provider');
            }
            if (Schema::hasColumn('echeances', 'payment_origin')) {
                $table->dropColumn('payment_origin');
            }
        });
    }
};
