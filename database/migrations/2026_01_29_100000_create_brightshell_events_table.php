<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brightshell_events', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->date('date');
            $table->string('heure', 5)->nullable();      // "09:00" début
            $table->string('heure_fin', 5)->nullable();  // "10:30" fin
            $table->string('type')->default('autre');    // rdv, deadline, rappel, autre
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brightshell_events');
    }
};
