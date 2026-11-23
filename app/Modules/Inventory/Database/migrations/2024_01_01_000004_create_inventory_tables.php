<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory module.
 *
 * `boxes` = individual packed units (serialised, tracked by scan).
 * `stock_moves` = append-only ledger; balances are always derived from it
 * (never stored as a mutable number), matching the legacy design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boxes', function (Blueprint $table) {
            $table->bigIncrements('sn');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('weight', 12, 3);
            $table->string('scale_code')->nullable();
            $table->foreignId('prod_order_id')->nullable();
            $table->char('packed_by', 36)->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('location_id', 64);
            $table->string('status', 20)->default('stock')->index(); // stock|loaded|sold|waste|donated
            $table->string('crate')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->timestamp('at')->nullable();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('location_id', 64);
            $table->decimal('qty', 12, 3); // +ve = in, -ve = out
            $table->string('kind', 20); // pack|receive|sale|transfer|adjust|waste|donate|count|return|production
            $table->text('reason')->nullable();
            $table->string('ref')->nullable();
            $table->unsignedBigInteger('box_sn')->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->char('by', 36)->nullable();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('box_sn')->references('sn')->on('boxes')->nullOnDelete();

            $table->index(['item_id', 'location_id']);
            $table->index('ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_moves');
        Schema::dropIfExists('boxes');
    }
};