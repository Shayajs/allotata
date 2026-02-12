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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Ajouter la colonne type si elle n'existe pas
            if (!Schema::hasColumn('subscriptions', 'type')) {
                $table->string('type')->nullable()->after('user_id');
            }
        });
        
        // Copier les valeurs de 'name' vers 'type'
        \DB::statement('UPDATE subscriptions SET type = name WHERE type IS NULL');
        
        // Rendre la colonne non nullable
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
