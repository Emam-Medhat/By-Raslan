<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chart of accounts and journal entries for double-entry accounting.
 * Mirrors Nano City's accounting tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->default('asset');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_posting')->default(true);
            $table->string('location_id', 64)->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
            $table->index('type');
            $table->index('location_id');
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('location_id', 64)->nullable();
            $table->timestamps();

            $table->index('reference_number');
            $table->index('date');
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        // Seed default accounts
        $accounts = [
            ['code' => '1101', 'name' => 'الصندوق', 'type' => 'asset', 'allow_posting' => true],
            ['code' => '1102', 'name' => 'البنك', 'type' => 'asset', 'allow_posting' => true],
            ['code' => '1300', 'name' => 'المخزون', 'type' => 'asset', 'allow_posting' => true],
            ['code' => '2100', 'name' => 'الموردون', 'type' => 'liability', 'allow_posting' => true],
            ['code' => '2200', 'name' => 'الضرائب المستحقة', 'type' => 'liability', 'allow_posting' => true],
            ['code' => '3100', 'name' => 'رأس المال', 'type' => 'equity', 'allow_posting' => true],
            ['code' => '4100', 'name' => 'إيرادات المبيعات', 'type' => 'revenue', 'allow_posting' => true],
            ['code' => '5200', 'name' => 'مصروفات التشغيل', 'type' => 'expense', 'allow_posting' => true],
            ['code' => '5300', 'name' => 'عجز المخزون', 'type' => 'expense', 'allow_posting' => true],
            ['code' => '5400', 'name' => 'تكلفة البضاعة المباعة', 'type' => 'expense', 'allow_posting' => true],
            ['code' => '6100', 'name' => 'فروقات الصندوق', 'type' => 'expense', 'allow_posting' => true],
            ['code' => '6200', 'name' => 'أرباح وخسائر استبعاد أصول', 'type' => 'expense', 'allow_posting' => true],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->insert($account + ['is_active' => true, 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};