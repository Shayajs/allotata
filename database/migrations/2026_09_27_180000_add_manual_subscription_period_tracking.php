<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'abonnement_manuel_statut')) {
                $table->string('abonnement_manuel_statut', 20)->nullable()->after('abonnement_manuel');
                $table->index('abonnement_manuel_statut');
            }
        });

        $today = now()->toDateString();

        DB::table('users')
            ->where('abonnement_manuel', true)
            ->whereNotNull('abonnement_manuel_actif_jusqu')
            ->whereDate('abonnement_manuel_actif_jusqu', '>=', $today)
            ->update(['abonnement_manuel_statut' => 'active']);

        DB::table('users')
            ->where('abonnement_manuel', true)
            ->whereNull('abonnement_manuel_statut')
            ->update(['abonnement_manuel_statut' => 'ended']);

        Schema::create('abonnement_manuel_periodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('echeance_at');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->string('statut', 32);
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refused_at')->nullable();
            $table->foreignId('refused_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('declared_at')->nullable();
            $table->timestamp('notified_due_at')->nullable();
            $table->timestamp('notified_overdue_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'echeance_at']);
            $table->index(['statut', 'echeance_at']);
        });

        Schema::create('abonnement_manuel_evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('abonnement_manuel_periode_id')->nullable();
            $table->foreign('abonnement_manuel_periode_id', 'am_evt_periode_fk')
                ->references('id')
                ->on('abonnement_manuel_periodes')
                ->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('message');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnement_manuel_evenements');
        Schema::dropIfExists('abonnement_manuel_periodes');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'abonnement_manuel_statut')) {
                $table->dropIndex(['abonnement_manuel_statut']);
                $table->dropColumn('abonnement_manuel_statut');
            }
        });
    }
};
