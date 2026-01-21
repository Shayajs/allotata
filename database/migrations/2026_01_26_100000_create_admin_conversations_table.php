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
        Schema::create('admin_conversations', function (Blueprint $table) {
            $table->id();
            
            // Métadonnées
            $table->string('nom')->nullable(); // Nom pour groupes futurs
            $table->boolean('est_groupe')->default(false);
            $table->timestamp('dernier_message_at')->nullable();
            
            $table->timestamps();
            
            $table->index('dernier_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_conversations');
    }
};
