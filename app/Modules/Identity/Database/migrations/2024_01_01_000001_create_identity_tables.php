<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity & Access: roles, permissions catalog, role↔permission,
 * role↔user, and per-user grants (with optional expiry + amount limit).
 *
 * A "grant" (permission_user) is the Laravel equivalent of the legacy
 * `grants` table: a direct permission assigned to a user, optionally time
 * bound (until) and amount bound (max_amount).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // e.g. "pos.sell"
            $table->string('grp', 64)->index();   // e.g. "البيع"
            $table->string('label');
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('permission_id', 64);
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->primary(['role_id', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->char('user_id', 36);
            $table->string('permission_id', 64);
            $table->date('until')->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->char('granted_by', 36)->nullable();
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};