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
        // D'abord, récupérer les données à migrer AVANT de supprimer les colonnes
        $entreprisesAvecAbonnement = \DB::table('entreprises')
            ->where('abonnement_manuel', true)
            ->whereNotNull('abonnement_manuel_actif_jusqu')
            ->select('user_id', 'abonnement_manuel_actif_jusqu', 'abonnement_manuel_notes')
            ->get();

        // Ajouter les champs d'abonnement manuel à la table users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'abonnement_manuel')) {
                $table->boolean('abonnement_manuel')->default(false)->after('trial_ends_at');
                $table->date('abonnement_manuel_actif_jusqu')->nullable()->after('abonnement_manuel');
                $table->text('abonnement_manuel_notes')->nullable()->after('abonnement_manuel_actif_jusqu');
            }
        });

        // Migrer les données existantes des entreprises vers les users
        foreach ($entreprisesAvecAbonnement as $entreprise) {
            \DB::table('users')
                ->where('id', $entreprise->user_id)
                ->update([
                    'abonnement_manuel' => true,
                    'abonnement_manuel_actif_jusqu' => $entreprise->abonnement_manuel_actif_jusqu,
                    'abonnement_manuel_notes' => $entreprise->abonnement_manuel_notes,
                ]);
        }

        // Supprimer les champs de la table entreprises
        Schema::table('entreprises', function (Blueprint $table) {
            if (Schema::hasColumn('entreprises', 'abonnement_manuel')) {
                $table->dropColumn(['abonnement_manuel', 'abonnement_manuel_actif_jusqu', 'abonnement_manuel_notes']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recréer les champs dans entreprises
        Schema::table('entreprises', function (Blueprint $table) {
            if (!Schema::hasColumn('entreprises', 'abonnement_manuel')) {
                $table->boolean('abonnement_manuel')->default(false)->after('afficher_nom_gerant');
                $table->date('abonnement_manuel_actif_jusqu')->nullable()->after('abonnement_manuel');
                $table->text('abonnement_manuel_notes')->nullable()->after('abonnement_manuel_actif_jusqu');
            }
        });

        // Migrer les données des users vers les entreprises
        $users = \DB::table('users')
            ->where('abonnement_manuel', true)
            ->whereNotNull('abonnement_manuel_actif_jusqu')
            ->get();

        foreach ($users as $user) {
            \DB::table('entreprises')
                ->where('user_id', $user->id)
                ->update([
                    'abonnement_manuel' => true,
                    'abonnement_manuel_actif_jusqu' => $user->abonnement_manuel_actif_jusqu,
                    'abonnement_manuel_notes' => $user->abonnement_manuel_notes,
                ]);
        }

        // Supprimer les champs de la table users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'abonnement_manuel')) {
                $table->dropColumn(['abonnement_manuel', 'abonnement_manuel_actif_jusqu', 'abonnement_manuel_notes']);
            }
        });
    }
};
