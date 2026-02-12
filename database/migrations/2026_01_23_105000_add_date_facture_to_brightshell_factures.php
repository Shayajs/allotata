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
        Schema::table('brightshell_factures', function (Blueprint $table) {
            $table->date('date_facture')->nullable()->after('client_id');
        });
        
        // Rétro-compatibilité : remplir date_facture avec created_at pour les anciennes factures
        DB::table('brightshell_factures')
            ->whereNull('date_facture')
            ->update(['date_facture' => DB::raw('DATE(created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brightshell_factures', function (Blueprint $table) {
            $table->dropColumn('date_facture');
        });
    }
};
