<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            $table->string('stripe_refund_id')->nullable()->after('stripe_payment_intent_id');
            $table->decimal('refund_amount', 8, 2)->nullable()->after('stripe_refund_id');
            $table->string('refund_status')->nullable()->after('refund_amount'); // succeeded, pending, failed
            $table->string('refund_reason')->nullable()->after('refund_status');
            $table->text('refund_notes')->nullable()->after('refund_reason');
            $table->unsignedBigInteger('refunded_by')->nullable()->after('refund_notes');
            $table->timestamp('refunded_at')->nullable()->after('refunded_by');

            $table->foreign('refunded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn([
                'stripe_refund_id',
                'refund_amount',
                'refund_status',
                'refund_reason',
                'refund_notes',
                'refunded_by',
                'refunded_at',
            ]);
        });
    }
};
