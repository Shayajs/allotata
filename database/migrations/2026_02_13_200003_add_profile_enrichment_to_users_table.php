<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identité / Profil
            $table->enum('genre', ['homme', 'femme', 'non_precise'])->default('non_precise')->after('date_naissance');

            // Langue
            $table->string('langue_preferee', 5)->default('fr')->after('genre');

            // Marketing / Acquisition
            $table->enum('source_inscription', ['google', 'bouche_a_oreille', 'reseaux_sociaux', 'publicite', 'parrainage', 'autre'])->nullable()->after('langue_preferee');
            $table->string('code_parrain', 10)->unique()->nullable()->after('source_inscription');
            $table->foreignId('parrain_id')->nullable()->constrained('users')->nullOnDelete()->after('code_parrain');

            // Activité
            $table->timestamp('derniere_connexion_at')->nullable()->after('parrain_id');

            // Préférences horaires
            $table->boolean('pref_horaire_matin')->default(true)->after('derniere_connexion_at');
            $table->boolean('pref_horaire_apres_midi')->default(true)->after('pref_horaire_matin');
            $table->boolean('pref_horaire_soir')->default(false)->after('pref_horaire_apres_midi');
            $table->boolean('pref_horaire_weekend')->default(true)->after('pref_horaire_soir');

            // Contact d'urgence
            $table->string('urgence_nom')->nullable()->after('pref_horaire_weekend');
            $table->string('urgence_telephone')->nullable()->after('urgence_nom');

            // Santé / Allergies
            $table->text('allergies_notes')->nullable()->after('urgence_telephone');

            // Préférences prestataire
            $table->enum('pref_prestataire_genre', ['homme', 'femme', 'indifferent'])->default('indifferent')->after('allergies_notes');
            $table->unsignedTinyInteger('pref_prestataire_experience_min')->nullable()->after('pref_prestataire_genre');

            // Notes personnelles (visibles par les prestataires)
            $table->text('notes_prestataires')->nullable()->after('pref_prestataire_experience_min');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parrain_id']);
            $table->dropColumn([
                'genre',
                'langue_preferee',
                'source_inscription',
                'code_parrain',
                'parrain_id',
                'derniere_connexion_at',
                'pref_horaire_matin',
                'pref_horaire_apres_midi',
                'pref_horaire_soir',
                'pref_horaire_weekend',
                'urgence_nom',
                'urgence_telephone',
                'allergies_notes',
                'pref_prestataire_genre',
                'pref_prestataire_experience_min',
                'notes_prestataires',
            ]);
        });
    }
};
