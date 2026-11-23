<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orders & Delivery: customers, online orders, order lines and scanned boxes.
 * Customers order without login; the branch prepares by scanning boxes, then
 * dispatches and collects on delivery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('area')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->string('token')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('location_id', 64);
            $table->string('channel', 10)->default('web'); // web|phone|whatsapp
            $table->string('status', 12)->default('new'); // new|accepted|preparing|ready|out|delivered|cancelled
            $table->string('address')->nullable();
            $table->text('note')->nullable();
            $table->string('pay_method', 10)->default('cash'); // cash|card|instapay
            $table->decimal('est_total', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->nullable();
            $table->boolean('paid')->default(false);
            $table->string('driver')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->char('handled_by', 36)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('out_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['location_id', 'status']);
        });

        Schema::create('online_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('online_orders')->cascadeOnDelete();
            $table->integer('line');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('prepared', 12, 3)->default(0);

            $table->unique(['order_id', 'line']);
        });

        Schema::create('online_order_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('online_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('box_sn');
            $table->integer('line');
            $table->decimal('qty', 12, 3);
            $table->boolean('removed')->default(false);
            $table->timestamp('at')->nullable();
            // Holds box_sn while active, NULL once removed -> unique active box.
            $table->unsignedBigInteger('active_box')->nullable();

            $table->unique('active_box');
            $table->foreign('box_sn')->references('sn')->on('boxes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_boxes');
        Schema::dropIfExists('online_order_lines');
        Schema::dropIfExists('online_orders');
        Schema::dropIfExists('customers');
    }
};