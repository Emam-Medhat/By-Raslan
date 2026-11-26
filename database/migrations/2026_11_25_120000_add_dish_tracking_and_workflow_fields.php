<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'shelf_life_days')) {
                $table->unsignedSmallInteger('shelf_life_days')->nullable()->after('scale_plu');
            }
        });

        Schema::table('boxes', function (Blueprint $table) {
            if (!Schema::hasColumn('boxes', 'uid')) {
                $table->string('uid', 32)->nullable()->unique()->after('sn');
            }
            if (!Schema::hasColumn('boxes', 'produced_at')) {
                $table->timestamp('produced_at')->nullable()->after('packed_at');
            }
            if (!Schema::hasColumn('boxes', 'source_location_id')) {
                $table->string('source_location_id', 64)->nullable()->after('location_id');
            }
            if (!Schema::hasColumn('boxes', 'sold_at')) {
                $table->timestamp('sold_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('boxes', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('sold_at');
            }
            if (!Schema::hasColumn('boxes', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('sale_id');
            }
            if (!Schema::hasColumn('boxes', 'meta')) {
                $table->json('meta')->nullable()->after('crate');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('cashier');
            }
            if (!Schema::hasColumn('sales', 'customer_phone')) {
                $table->string('customer_phone', 32)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('sales', 'delivery_fee')) {
                $table->decimal('delivery_fee', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('sales', 'pay_method')) {
                $table->string('pay_method', 20)->nullable()->after('change');
            }
            if (!Schema::hasColumn('sales', 'payment_status')) {
                $table->string('payment_status', 20)->default('paid')->after('pay_method');
            }
            if (!Schema::hasColumn('sales', 'collected_at')) {
                $table->timestamp('collected_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('sales', 'collected_by')) {
                $table->char('collected_by', 36)->nullable()->after('collected_at');
            }
            if (!Schema::hasColumn('sales', 'delivery_status')) {
                $table->string('delivery_status', 20)->default('pending')->after('collected_by');
            }
            if (!Schema::hasColumn('sales', 'driver_id')) {
                $table->char('driver_id', 36)->nullable()->after('delivery_status');
            }
            if (!Schema::hasColumn('sales', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('driver_id');
            }
            if (!Schema::hasColumn('sales', 'order_source')) {
                $table->string('order_source', 20)->default('pos')->after('delivered_at'); // pos|app|call_center|reservation
            }
            if (!Schema::hasColumn('sales', 'online_order_id')) {
                $table->unsignedBigInteger('online_order_id')->nullable()->after('order_source');
            }
            if (!Schema::hasColumn('sales', 'reservation_id')) {
                $table->unsignedBigInteger('reservation_id')->nullable()->after('online_order_id');
            }
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_returns', 'destination')) {
                $table->string('destination', 20)->default('stock')->after('status'); // stock|waste|donation
            }
            if (!Schema::hasColumn('sales_returns', 'destination_location_id')) {
                $table->string('destination_location_id', 64)->nullable()->after('destination');
            }
        });

        Schema::table('sales_return_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_return_lines', 'destination')) {
                $table->string('destination', 20)->nullable()->after('total');
            }
            if (!Schema::hasColumn('sales_return_lines', 'destination_location_id')) {
                $table->string('destination_location_id', 64)->nullable()->after('destination');
            }
        });

        Schema::table('online_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('online_orders', 'driver_id')) {
                $table->char('driver_id', 36)->nullable()->after('driver');
            }
            if (!Schema::hasColumn('online_orders', 'collection_method')) {
                $table->string('collection_method', 20)->nullable()->after('pay_method');
            }
            if (!Schema::hasColumn('online_orders', 'collected_amount')) {
                $table->decimal('collected_amount', 12, 2)->nullable()->after('collection_method');
            }
            if (!Schema::hasColumn('online_orders', 'collected_by')) {
                $table->char('collected_by', 36)->nullable()->after('collected_amount');
            }
            if (!Schema::hasColumn('online_orders', 'collected_at')) {
                $table->timestamp('collected_at')->nullable()->after('collected_by');
            }
            if (!Schema::hasColumn('online_orders', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('collected_at');
            }
            if (!Schema::hasColumn('online_orders', 'reservation_id')) {
                $table->unsignedBigInteger('reservation_id')->nullable()->after('sale_id');
            }
        });

        if (!Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('no')->unique();
                $table->string('location_id', 64);
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone', 32)->nullable();
                $table->string('customer_address')->nullable();
                $table->date('scheduled_for');
                $table->string('status', 20)->default('pending'); // pending|confirmed|converted|cancelled
                $table->text('notes')->nullable();
                $table->char('created_by', 36)->nullable();
                $table->char('confirmed_by', 36)->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->unsignedBigInteger('online_order_id')->nullable();
                $table->timestamp('converted_at')->nullable();
                $table->timestamps();
                $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
                $table->index(['location_id', 'scheduled_for', 'status']);
            });
        }

        if (!Schema::hasTable('reservation_lines')) {
            Schema::create('reservation_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->decimal('qty', 12, 3);
                $table->text('note')->nullable();
            });
        }

        if (!Schema::hasTable('box_uid_seq')) {
            Schema::create('box_uid_seq', function (Blueprint $table) {
                $table->id();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_driver')) {
                $table->boolean('is_driver')->default(false)->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_lines');
        Schema::dropIfExists('reservations');
        // non-destructive down omitted for additive columns in production safety
    }
};
