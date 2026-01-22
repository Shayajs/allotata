<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brightshell_factures', function (Blueprint $table) {
            $table->string('statut')->default('brouillon')->after('notes'); // brouillon, envoyee, payee, annulee
        });
        
        // Mettre à jour les factures existantes selon est_payee
        \DB::table('brightshell_factures')
            ->where('est_payee', true)
            ->update(['statut' => 'payee']);
    }

    public function down(): void
    {
        Schema::table('brightshell_factures', function (Blueprint $table) {
            $table->dropColumn('statut');
        });
    }
};
