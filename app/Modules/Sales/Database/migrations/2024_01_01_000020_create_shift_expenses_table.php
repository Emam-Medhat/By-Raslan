<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shift expenses: expenses linked to a specific shift with approval workflow.
 * Mirrors Nano City's shift_expenses table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->string('location_id', 64);
            $table->string('title');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamp('expense_date')->nullable();
            $table->string('status', 10)->default('pending'); // pending, approved, rejected
            $table->char('approved_by', 36)->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['shift_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_expenses');
    }
};