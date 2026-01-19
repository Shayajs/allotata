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
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ip_address', 45);
            $table->string('device_hash', 64); // Hash du user agent pour identifier le périphérique
            $table->string('user_agent')->nullable(); // User agent complet pour référence
            $table->string('country_code', 2)->nullable();
            $table->string('location')->nullable(); // Pays/Ville
            $table->timestamp('first_used_at');
            $table->timestamp('last_used_at');
            $table->integer('usage_count')->default(1);
            $table->timestamps();
            
            // Index pour les recherches rapides
            $table->index('user_id');
            $table->index(['user_id', 'ip_address', 'device_hash']); // Recherche combinée
            $table->index(['user_id', 'device_hash']);
            $table->index('last_used_at');
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
