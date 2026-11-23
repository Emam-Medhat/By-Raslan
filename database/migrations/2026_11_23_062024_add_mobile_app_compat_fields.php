<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'auth_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->char('auth_id', 36)->nullable()->after('id');
                $table->index('auth_id');
            });
        }

        if (! Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('label')->default('البيت');
                $table->string('area')->nullable();
                $table->string('address');
                $table->string('location_id', 64)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('online_orders', 'from_app')) {
            Schema::table('online_orders', function (Blueprint $table) {
                $table->boolean('from_app')->default(false)->after('channel');
                $table->unsignedBigInteger('address_id')->nullable()->after('customer_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('online_orders', 'address_id')) {
            Schema::table('online_orders', function (Blueprint $table) {
                $table->dropColumn(['from_app', 'address_id']);
            });
        }
        Schema::dropIfExists('customer_addresses');
        if (Schema::hasColumn('customers', 'auth_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('auth_id');
            });
        }
    }
};
