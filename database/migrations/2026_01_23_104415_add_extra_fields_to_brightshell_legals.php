<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brightshell_legals', function (Blueprint $table) {
            if (!Schema::hasColumn('brightshell_legals', 'destinataire_prenom')) {
                $table->string('destinataire_prenom')->nullable()->after('destinataire_nom');
            }
            if (!Schema::hasColumn('brightshell_legals', 'destinataire_titre')) {
                $table->string('destinataire_titre')->nullable()->after('destinataire_prenom');
            }
            if (!Schema::hasColumn('brightshell_legals', 'pieces_jointes')) {
                $table->text('pieces_jointes')->nullable()->after('contenu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brightshell_legals', function (Blueprint $table) {
            $table->dropColumn(['destinataire_prenom', 'destinataire_titre', 'pieces_jointes']);
        });
    }
};
