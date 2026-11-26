<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Drivers + order collection fields.
 *
 * Drivers are delivery staff linked to one branch (location_id). A driver
 * may optionally be linked to a staff user account (user_id) — the link is
 * for accountability only; the driver is chosen by branch, not by login.
 * Old free-text `online_orders.driver` values are kept as-is for reading.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36)->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('location_id', 64);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['location_id', 'active']);
        });

        Schema::table('online_orders', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('driver')->constrained('drivers')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('driver_id')->constrained('shifts')->nullOnDelete();
            $table->string('collection_method', 10)->nullable()->after('shift_id'); // cash|instapay|card
            $table->timestamp('collected_at')->nullable()->after('collection_method');
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['driver_id', 'shift_id', 'collection_method', 'collected_at']);
        });
        Schema::dropIfExists('drivers');
    }
};
