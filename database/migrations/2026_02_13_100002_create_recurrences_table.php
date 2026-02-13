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
        Schema::create('recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('type_service_id')->constrained('types_services')->onDelete('cascade');
            $table->foreignId('membre_id')->nullable()->constrained('entreprise_membres')->onDelete('set null');
            $table->string('frequence'); // hebdomadaire, bimensuel, mensuel, personnalise
            $table->integer('intervalle_jours')->nullable(); // pour personnalise
            $table->date('date_debut');
            $table->date('date_fin');
            $table->time('heure'); // heure du créneau récurrent
            $table->string('lieu')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('prix_par_occurrence', 10, 2);
            $table->boolean('est_active')->default(true);
            // Client non inscrit
            $table->string('nom_client')->nullable();
            $table->string('email_client')->nullable();
            $table->string('telephone_client')->nullable();
            $table->timestamps();

            $table->index('entreprise_id');
            $table->index('user_id');
            $table->index('type_service_id');
            $table->index('est_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrences');
    }
};
