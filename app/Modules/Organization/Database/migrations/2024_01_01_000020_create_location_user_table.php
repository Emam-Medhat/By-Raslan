<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Location-User pivot: links users to locations with role and per-location permissions.
 * Mirrors Nano City's branch_users table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->string('location_id', 64);
            $table->char('user_id', 36);
            $table->string('role', 20)->default('cashier'); // owner, manager, cashier, staff
            $table->boolean('is_primary')->default(false);
            $table->json('permissions')->nullable(); // per-location permission overrides
            $table->char('assigned_by', 36)->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['location_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};