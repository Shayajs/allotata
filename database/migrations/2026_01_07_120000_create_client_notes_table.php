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
        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Client concerné
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade'); // Créateur de la note
            $table->text('note');
            $table->json('tags')->nullable(); // Tags comme ['VIP', 'régulier', 'allergie']
            $table->boolean('is_important')->default(false);
            $table->timestamps();
            
            $table->index(['entreprise_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_notes');
    }
};
