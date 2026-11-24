<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cash transfers: money moved between shifts, to manager, or to safe.
 * Mirrors Nano City's cash_transfers table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_shift_id')->nullable();
            $table->unsignedBigInteger('to_shift_id')->nullable();
            $table->string('location_id', 64)->nullable();
            $table->char('from_user_id', 36)->nullable();
            $table->char('to_user_id', 36)->nullable();
            $table->string('type', 20)->default('to_manager'); // to_manager, to_next_shift, to_safe
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 10)->default('approved'); // pending, approved, rejected
            $table->char('approved_by', 36)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('from_shift_id')->references('id')->on('shifts')->nullOnDelete();
            $table->foreign('to_shift_id')->references('id')->on('shifts')->nullOnDelete();
            $table->index(['from_shift_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transfers');
    }
};