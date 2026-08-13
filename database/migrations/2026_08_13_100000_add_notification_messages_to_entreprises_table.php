<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->text('notif_message_prise')->nullable()->after('intervalle_creneaux_minutes');
            $table->text('notif_message_annulation')->nullable()->after('notif_message_prise');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['notif_message_prise', 'notif_message_annulation']);
        });
    }
};
