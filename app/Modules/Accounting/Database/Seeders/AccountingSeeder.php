<?php

namespace App\Modules\Accounting\Database\Seeders;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Accounting\Models\ExpenseCategory;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalEntryLine;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data
            \App\Modules\Accounting\Models\Expense::query()->delete();
            \App\Modules\Accounting\Models\JournalEntryLine::query()->delete();
            \App\Modules\Accounting\Models\JournalEntry::query()->delete();
            \App\Modules\Accounting\Models\Account::query()->delete();
            \App\Modules\Accounting\Models\ExpenseCategory::query()->delete();

            // Expense Categories
            $expenseCategories = [
                ['name' => 'إيجار', 'active' => true],
                ['name' => 'كهرباء ومياه', 'active' => true],
                ['name' => 'صيانة معدات', 'active' => true],
                ['name' => 'رواتب', 'active' => true],
                ['name' => 'تسويق وإعلان', 'active' => true],
                ['name' => 'مستلزمات مكتبية', 'active' => true],
                ['name' => 'نقل ومواصلات', 'active' => true],
                ['name' => 'رسوم حكومية', 'active' => true],
                ['name' => 'تأمينات', 'active' => true],
                ['name' => 'أخرى', 'active' => true],
            ];

            foreach ($expenseCategories as $index => $cat) {
                ExpenseCategory::updateOrCreate(
                    ['id' => $index + 1],
                    $cat
                );
            }

// Chart of Accounts
            $accounts = [
                // Assets
                ['code' => '1000', 'name' => 'الأصول', 'type' => 'asset', 'parent_id' => null, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                
                ['code' => '1100', 'name' => 'الأصول المتداولة', 'type' => 'asset', 'parent_id' => 1, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                ['code' => '1110', 'name' => 'النقدية', 'type' => 'asset', 'parent_id' => 2, 'balance' => 500000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 300000],
                ['code' => '1120', 'name' => 'البنوك', 'type' => 'asset', 'parent_id' => 2, 'balance' => 1200000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 800000],
                ['code' => '1130', 'name' => 'المخزون', 'type' => 'asset', 'parent_id' => 2, 'balance' => 800000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 600000],
                ['code' => '1140', 'name' => 'العملاء', 'type' => 'asset', 'parent_id' => 2, 'balance' => 350000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 200000],
                
                ['code' => '1200', 'name' => 'الأصول الثابتة', 'type' => 'asset', 'parent_id' => 1, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                ['code' => '1210', 'name' => 'المباني', 'type' => 'asset', 'parent_id' => 7, 'balance' => 2000000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 2000000],
                ['code' => '1220', 'name' => 'المعدات والآلات', 'type' => 'asset', 'parent_id' => 7, 'balance' => 1500000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 1500000],
                ['code' => '1230', 'name' => 'السيارات', 'type' => 'asset', 'parent_id' => 7, 'balance' => 800000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 800000],
    
                // Liabilities
                ['code' => '2000', 'name' => 'الخصوم', 'type' => 'liability', 'parent_id' => null, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                ['code' => '2100', 'name' => 'الموردون', 'type' => 'liability', 'parent_id' => 11, 'balance' => 400000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 300000],
                ['code' => '2200', 'name' => 'القروض قصيرة الأجل', 'type' => 'liability', 'parent_id' => 11, 'balance' => 500000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 500000],
    
                // Equity
                ['code' => '3000', 'name' => 'حقوق الملكية', 'type' => 'equity', 'parent_id' => null, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                ['code' => '3100', 'name' => 'رأس المال', 'type' => 'equity', 'parent_id' => 14, 'balance' => 3000000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 3000000],
                ['code' => '3200', 'name' => 'الأرباح المحتجزة', 'type' => 'equity', 'parent_id' => 14, 'balance' => 500000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 300000],
    
                // Revenue
                ['code' => '4000', 'name' => 'الإيرادات', 'type' => 'revenue', 'parent_id' => null, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                ['code' => '4100', 'name' => 'مبيعات نقدية', 'type' => 'revenue', 'parent_id' => 17, 'balance' => 2500000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '4200', 'name' => 'مبيعات آجلة', 'type' => 'revenue', 'parent_id' => 17, 'balance' => 800000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '4300', 'name' => 'إيرادات أخرى', 'type' => 'revenue', 'parent_id' => 17, 'balance' => 100000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
    
                // Expenses
                ['code' => '5000', 'name' => 'المصروفات', 'type' => 'expense', 'parent_id' => null, 'balance' => 0, 'is_active' => true, 'allow_posting' => false, 'opening_balance' => 0],
                ['code' => '5100', 'name' => 'تكلفة المبيعات', 'type' => 'expense', 'parent_id' => 21, 'balance' => 1800000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5200', 'name' => 'إيجارات', 'type' => 'expense', 'parent_id' => 21, 'balance' => 120000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5300', 'name' => 'مرافق', 'type' => 'expense', 'parent_id' => 21, 'balance' => 80000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5400', 'name' => 'رواتب وأجور', 'type' => 'expense', 'parent_id' => 21, 'balance' => 600000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5500', 'name' => 'صيانة', 'type' => 'expense', 'parent_id' => 21, 'balance' => 50000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5600', 'name' => 'تسويق', 'type' => 'expense', 'parent_id' => 21, 'balance' => 30000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5700', 'name' => 'نقل', 'type' => 'expense', 'parent_id' => 21, 'balance' => 40000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
                ['code' => '5800', 'name' => 'مصروفات أخرى', 'type' => 'expense', 'parent_id' => 21, 'balance' => 20000, 'is_active' => true, 'allow_posting' => true, 'opening_balance' => 0],
            ];

            foreach ($accounts as $index => $account) {
                Account::updateOrCreate(
                    ['id' => $index + 1],
                    $account
                );
            }

            // Journal Entries - create some sample entries
            $locations = Location::pluck('id')->toArray();
            
            for ($i = 1; $i <= 50; $i++) {
                $date = now()->subDays(rand(0, 60))->format('Y-m-d');
                $locationId = $locations[array_rand($locations)];
                
                $entry = JournalEntry::create([
                    'date' => $date,
                    'description' => 'قيد يومية رقم ' . $i,
                    'reference_number' => 'JE-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'location_id' => $locationId,
                ]);

                // Create 2-4 lines per entry (balanced)
                $numLines = rand(2, 4);
                $debitTotal = 0;
                $creditTotal = 0;
                
                for ($l = 1; $l <= $numLines; $l++) {
                    $isDebit = $l <= $numLines / 2 || $debitTotal <= $creditTotal;
                    $amount = rand(1000, 100000);
                    
                    if ($isDebit) {
                        $debitTotal += $amount;
                        $credit = 0;
                    } else {
                        $creditTotal += $amount;
                        $amount = 0;
                    }
                    
                    // For the last line, balance it
                    if ($l === $numLines) {
                        if ($debitTotal > $creditTotal) {
                            $amount = 0;
                            $credit = $debitTotal - $creditTotal;
                        } else {
                            $credit = 0;
                            $amount = $creditTotal - $debitTotal;
                        }
                    }
                    
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => rand(1, count($accounts)),
                        'debit' => $amount,
                        'credit' => $credit,
                    ]);
                }
            }

            // Expenses
            $expenseNotes = [
                'إيجار شهر ' . now()->format('m/Y'),
                'فاتورة كهرباء شهر ' . now()->format('m/Y'),
                'صيانة ثلاجات العرض',
                'رواتب شهر ' . now()->format('m/Y'),
                'إعلانات فيسبوك',
                'ورق وأقلام مكتب',
                'بنزين سيارات التوزيع',
                'تجديد رخصة تجارية',
                'تأمين على المخزون',
                'تنظيف عميق للمصنع',
            ];
            
            $paidFromOptions = ['cash', 'bank'];
            $categories = ExpenseCategory::where('active', true)->pluck('id')->toArray();
            $shiftIds = \App\Modules\Sales\Models\Shift::pluck('id')->toArray();
            if (empty($shiftIds)) {
                $shiftIds = [null];
            }
            $user = User::where('phone', '01000000000')->first();
            $userId = $user?->id;
            
            for ($i = 1; $i <= 100; $i++) {
                $day = now()->subDays(rand(0, 60))->format('Y-m-d');
                $locationId = $locations[array_rand($locations)];
                $categoryId = $categories[array_rand($categories)];
                
                Expense::create([
                    'no' => $i,
                    'day' => $day,
                    'location_id' => $locationId,
                    'category_id' => $categoryId,
                    'amount' => rand(100, 50000),
                    'paid_from' => $paidFromOptions[array_rand($paidFromOptions)],
                    'shift_id' => rand(1, 100) > 70 ? $shiftIds[array_rand($shiftIds)] : null,
                    'note' => $expenseNotes[array_rand($expenseNotes)],
                    'photo' => rand(1, 100) > 70 ? 'expenses/expense_' . $i . '.jpg' : null,
                    'status' => ['ok', 'pending', 'rejected', 'ok', 'ok'][array_rand([0,1,2,3,4])],
                    'by' => $userId,
                    'at' => now()->subDays(rand(0, 60)),
                    'decided_by' => rand(1, 100) > 30 ? $userId : null,
                    'decided_at' => rand(1, 100) > 30 ? now()->subDays(rand(0, 30)) : null,
                    'decision_note' => 'معتمد من الإدارة',
                ]);
            }
        });
    }
}