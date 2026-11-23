<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sales & POS: cashier shifts, invoices and lines.
 * Prices are resolved from the prices table (never trusted from the client),
 * stock is deducted via the append-only ledger, and each sale is idempotent
 * per cashier via a unique (cashier, idempotency_key) key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('location_id', 64);
            $table->char('cashier', 36)->index();
            $table->timestamp('opened_at')->nullable();
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->string('status', 10)->default('open'); // open|closed
            // Holds the cashier while the shift is open, NULL once closed.
            // A unique index on it guarantees "one open shift per cashier"
            // in a way that works on both MySQL 8 and MariaDB.
            $table->string('open_cashier', 36)->nullable()->unique();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('cashier')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no');
            $table->string('location_id', 64);
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->char('cashier', 36)->index();
            $table->timestamp('at')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_cash', 12, 2)->default(0);
            $table->decimal('paid_card', 12, 2)->default(0);
            $table->decimal('change', 12, 2)->default(0);
            $table->char('idempotency_key', 36)->nullable();
            $table->json('request_payload')->nullable();
            $table->char('discount_by', 36)->nullable();

            $table->unique('no');
            $table->unique(['cashier', 'idempotency_key']);
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['shift_id']);
            $table->index(['location_id', 'at']);
        });

        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->integer('line');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedBigInteger('box_sn')->nullable();
            $table->decimal('qty', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);

            $table->unique(['sale_id', 'line']);
            $table->foreign('box_sn')->references('sn')->on('boxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('shifts');
    }
};