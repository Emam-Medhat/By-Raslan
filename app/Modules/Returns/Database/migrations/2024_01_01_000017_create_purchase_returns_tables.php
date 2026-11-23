<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase returns: return goods to a supplier; approval deducts stock
 * (negative ledger lines) and records the credit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('location_id', 64);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 12)->default('pending'); // pending|approved|rejected
            $table->text('reason')->nullable();
            $table->char('by', 36)->nullable();
            $table->timestamp('at')->nullable();
            $table->char('decided_by', 36)->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->decimal('cost', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
    }
};