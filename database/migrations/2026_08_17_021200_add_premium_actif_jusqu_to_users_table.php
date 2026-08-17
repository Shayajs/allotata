<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'premium_actif_jusqu')) {
                $table->date('premium_actif_jusqu')
                    ->nullable()
                    ->after('jour_facturation')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'premium_actif_jusqu')) {
                $table->dropColumn('premium_actif_jusqu');
            }
        });
    }
};
