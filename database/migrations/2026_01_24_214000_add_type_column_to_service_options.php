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
        if (Schema::hasTable('service_options') && !Schema::hasColumn('service_options', 'type')) {
            Schema::table('service_options', function (Blueprint $table) {
                $table->string('type')->default('choix_unique')->after('nom');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_options') && Schema::hasColumn('service_options', 'type')) {
            Schema::table('service_options', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
