<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3a — sales returns: destination + refund + shift link.
 *
 * destination: where the returned tray goes — 'stock' (back to the branch
 * stock to be resold), 'waste' (هالك), 'donated' (مخزن تبرعات). One
 * destination per return; if two trays need different destinations the
 * staff creates two returns.
 * refund_method: 'cash' | 'card' | 'none' — how the customer was refunded.
 * Cash refunds reduce the expected custody of the shift recorded in
 * shift_id (the approver's open shift in the return branch, if any).
 * refund_amount is set on approval from the returned lines' totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->string('destination', 12)->default('stock')->after('location_id');
            $table->string('refund_method', 10)->default('cash')->after('destination');
            $table->decimal('refund_amount', 12, 2)->default(0)->after('refund_method');
            $table->foreignId('shift_id')->nullable()->after('sale_id')->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['destination', 'refund_method', 'refund_amount', 'shift_id']);
        });
    }
};
