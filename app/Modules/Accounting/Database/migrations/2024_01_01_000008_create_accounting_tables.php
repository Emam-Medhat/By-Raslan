<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting: expense categories and expenses.
 * An expense above the poster's `acc.post` limit becomes `pending` until an
 * approver with `acc.approve` approves it. Shift expenses reduce expected cash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->date('day');
            $table->string('location_id', 64);
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->decimal('amount', 12, 2);
            $table->string('paid_from', 10)->default('cash'); // cash|bank|shift
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('photo')->nullable();
            $table->string('status', 10)->default('ok'); // ok|pending|rejected
            $table->char('by', 36);
            $table->timestamp('at')->nullable();
            $table->char('decided_by', 36)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->index(['status']);
            $table->index(['day']);
        });

        DB::table('expense_categories')->insert(
            collect(['إيجار', 'كهرباء', 'مياه', 'غاز', 'مرتبات ويوميات', 'صيانة', 'نقل وبنزين', 'تسويق', 'مستلزمات وأدوات نظافة', 'عمولات توصيل', 'أخرى'])
                ->map(fn ($name) => ['name' => $name, 'active' => true])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};