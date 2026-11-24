<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shift management enhancements:
 * - shifts: expenses_total, sent_to_manager, carryover_to_next_shift, notes, status enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'expenses_total')) {
                $table->decimal('expenses_total', 12, 2)->default(0)->after('expected_cash');
            }
            if (!Schema::hasColumn('shifts', 'sent_to_manager')) {
                $table->decimal('sent_to_manager', 12, 2)->default(0)->after('expenses_total');
            }
            if (!Schema::hasColumn('shifts', 'carryover_to_next_shift')) {
                $table->decimal('carryover_to_next_shift', 12, 2)->default(0)->after('sent_to_manager');
            }
            if (!Schema::hasColumn('shifts', 'notes')) {
                $table->text('notes')->nullable()->after('close_notes');
            }
            if (!Schema::hasColumn('shifts', 'status')) {
                $table->string('status', 10)->default('open'); // open, paused, closed
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['expenses_total', 'sent_to_manager', 'carryover_to_next_shift', 'notes', 'status']);
        });
    }
};