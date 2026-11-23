<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchasing & Suppliers: supply orders, goods receipt (with lots and stock
 * intake), supplier payments and override requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('terms')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('status', 12)->default('open'); // open|partial|done|cancelled
            $table->date('expected')->nullable();
            $table->text('note')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();

            $table->unique('no');
        });

        Schema::create('po_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->integer('line');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('received', 12, 3)->default(0);

            $table->unique(['po_id', 'line']);
        });

        Schema::create('override_requests', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10); // recv|...
            $table->string('ref')->nullable();
            $table->json('violations')->nullable();
            $table->decimal('value', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 12)->default('pending'); // pending|approved|rejected|used
            $table->char('decided_by', 36)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no');
            $table->foreignId('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('result')->default('مقبول');
            $table->json('flags')->nullable();
            $table->foreignId('override_id')->nullable()->constrained('override_requests')->nullOnDelete();
            $table->string('invoice_no')->nullable();
            $table->char('by', 36)->nullable();
            $table->timestamps();

            $table->unique('no');
        });

        Schema::create('receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->integer('line');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('weighed', 12, 3);
            $table->decimal('rejected', 12, 3)->default(0);
            $table->decimal('accepted', 12, 3);
            $table->decimal('price', 12, 2);
            $table->decimal('temp', 6, 2)->nullable();
            $table->date('expires')->nullable();
            $table->integer('spec_version')->nullable();
            $table->string('lot_code')->nullable();
            $table->text('note')->nullable();

            $table->unique(['receipt_id', 'line']);
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('qty', 12, 3);
            $table->decimal('cost', 12, 2)->default(0);
            $table->date('expires')->nullable();
            $table->string('location_id', 64)->default('main');
            $table->timestamps();

            // NOTE: no DB-level FK on location_id here. MariaDB 11.4 rejects a
            // table that mixes a varchar PK (code) + varchar FK (location) +
            // bigint FKs. location_id is validated in the service instead.
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 12)->default('cash'); // cash|bank|check|instapay
            $table->string('ref')->nullable();
            $table->text('note')->nullable();
            $table->char('by', 36)->nullable();
            $table->timestamps();

            $table->unique('no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('lots');
        Schema::dropIfExists('receipt_lines');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('override_requests');
        Schema::dropIfExists('po_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};