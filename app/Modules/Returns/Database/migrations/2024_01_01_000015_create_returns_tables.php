<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales returns: a return references a sale, restocks accepted items/boxes
 * on approval, and records the refund amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('location_id', 64);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 12)->default('pending'); // pending|approved|rejected
            $table->text('reason')->nullable();
            $table->char('by', 36)->nullable();
            $table->timestamp('at')->nullable();
            $table->char('decided_by', 36)->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('sales_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->unsignedBigInteger('sale_line_id')->nullable();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedBigInteger('box_sn')->nullable();
            $table->decimal('qty', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('sales_returns');
    }
};