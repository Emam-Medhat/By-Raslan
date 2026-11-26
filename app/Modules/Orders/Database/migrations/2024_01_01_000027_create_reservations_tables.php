<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3b — Reservations (الحجوزات).
 *
 * A reservation is an order taken for a future day (e.g. today Wednesday,
 * order for Saturday) or an order taken after work hours (after 4pm, for
 * tomorrow). On its date it converts to a real OnlineOrder — automatically
 * at 00:00 by the scheduler, or manually with the "حوّل اوردر" button after
 * confirming with the customer. A converted reservation never converts
 * twice (status guard in ReservationService::convert()).
 *
 * factory_reports stores the nightly factory report (per-item aggregated
 * quantities for tomorrow's reservations + notes) so the factory can open
 * it as a page; no e-mail is sent yet — there is no factory e-mail
 * configured in the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('location_id', 64);
            $table->date('reservation_date');
            $table->string('status', 12)->default('pending'); // pending|converted|cancelled
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->string('pay_method', 10)->default('cash'); // cash|card|instapay
            $table->text('note')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->foreignId('converted_order_id')->nullable()->constrained('online_orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['reservation_date', 'status']);
            $table->index(['location_id', 'reservation_date']);
        });

        Schema::create('reservation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->integer('line');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->decimal('unit_price', 12, 2)->nullable(); // price snapshot (channel 'app'), if any

            $table->unique(['reservation_id', 'line']);
        });

        Schema::create('factory_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_reports');
        Schema::dropIfExists('reservation_lines');
        Schema::dropIfExists('reservations');
    }
};
