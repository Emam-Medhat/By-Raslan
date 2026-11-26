<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Shift collections (custody ledger) + POS sale fields.
 *
 * shift_collections records every collection made while receiving an
 * online order (and, in future, a POS sale). Cash rows increase the
 * receiving user's custody in that branch/shift; instapay/card rows are
 * recorded for reports only and do NOT increase custody.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->string('location_id', 64);
            $table->foreignId('order_id')->nullable()->constrained('online_orders')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('method', 10); // cash|instapay|card
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['shift_id', 'method']);
            $table->index(['order_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('cashier')->constrained('customers')->nullOnDelete();
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('discount');
            $table->foreignId('driver_id')->nullable()->after('delivery_fee')->constrained('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['customer_id', 'delivery_fee', 'driver_id']);
        });
        Schema::dropIfExists('shift_collections');
    }
};
