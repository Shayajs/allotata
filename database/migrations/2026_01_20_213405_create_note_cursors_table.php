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
        Schema::create('note_cursors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->integer('selection_start')->nullable();
            $table->integer('selection_end')->nullable();
            $table->timestamp('updated_at');
            
            $table->unique(['note_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_cursors');
    }
};
