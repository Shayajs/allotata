<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_idempotency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('cle', 80);
            $table->unsignedSmallInteger('status');
            $table->json('reponse');
            $table->timestamps();
            $table->unique(['user_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency');
    }
};
