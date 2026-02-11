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
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone')->nullable()->after('email');
            $table->text('bio')->nullable()->after('telephone');
            $table->date('date_naissance')->nullable()->after('bio');
            $table->string('adresse')->nullable()->after('date_naissance');
            $table->string('ville')->nullable()->after('adresse');
            $table->string('code_postal', 10)->nullable()->after('ville');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telephone', 'bio', 'date_naissance', 'adresse', 'ville', 'code_postal']);
        });
    }
};
