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
            // Ajouter les nouvelles colonnes pour choix multiple
            $table->boolean('a2f_method_email')->default(true)->after('a2f_method');
            $table->boolean('a2f_method_sms')->default(false)->after('a2f_method_email');
            $table->boolean('recovery_method_email')->default(true)->after('preference_recovery_method');
            $table->boolean('recovery_method_sms')->default(false)->after('recovery_method_email');
        });

        // Migrer les données existantes
        DB::table('users')->where('a2f_method', 'email')->orWhereNull('a2f_method')->update([
            'a2f_method_email' => true,
            'a2f_method_sms' => false,
        ]);
        DB::table('users')->where('a2f_method', 'sms')->update([
            'a2f_method_email' => false,
            'a2f_method_sms' => true,
        ]);

        DB::table('users')->where('preference_recovery_method', 'email')->orWhereNull('preference_recovery_method')->update([
            'recovery_method_email' => true,
            'recovery_method_sms' => false,
        ]);
        DB::table('users')->where('preference_recovery_method', 'sms')->update([
            'recovery_method_email' => false,
            'recovery_method_sms' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['a2f_method_email', 'a2f_method_sms', 'recovery_method_email', 'recovery_method_sms']);
        });
    }
};
