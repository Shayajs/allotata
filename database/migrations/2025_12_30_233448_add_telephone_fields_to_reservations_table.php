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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('telephone_client')->nullable()->after('lieu');
            $table->boolean('telephone_cache')->default(false)->after('telephone_client');
            $table->foreignId('type_service_id')->nullable()->after('type_service')->constrained('types_services')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['type_service_id']);
            $table->dropColumn(['telephone_client', 'telephone_cache', 'type_service_id']);
        });
    }
};
