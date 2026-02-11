<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('forum_categories');
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->boolean('admin_only')->default(false); // Pour les nouveautés (admin uniquement)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_categories');
    }
};
