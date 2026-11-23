<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shift management enhancements:
 * - shifts: closer, notes, logout marker, POS terminal.
 * - stock_moves / audit_log: shift linkage for the movement report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('pos_terminal', 64)->nullable()->after('location_id');
            $table->char('closed_by', 36)->nullable()->after('cashier');
            $table->timestamp('logged_out_at')->nullable()->after('closed_at');
            $table->text('close_notes')->nullable()->after('expected_cash');
        });

        Schema::table('stock_moves', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('by');
            $table->index('shift_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['pos_terminal', 'closed_by', 'logged_out_at', 'close_notes']);
        });
    }
};