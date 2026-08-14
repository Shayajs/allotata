<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            // Seule l'empreinte est stockee : un jeton perdu ne se retrouve pas, il se remplace.
            $table->string('token_hash', 64)->unique();
            $table->string('apercu', 12);
            $table->timestamp('derniere_utilisation_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
