<?php

namespace App\Modules\Audit\Database\Seeders;

use App\Modules\Audit\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $users = User::pluck('id')->toArray();
            $actions = ['create', 'update', 'delete', 'approve', 'reject', 'close', 'open', 'transfer'];
            $tables = [
                'items', 'sales', 'sale_lines', 'shifts', 'boxes', 'stock_moves',
                'transfers', 'transfer_boxes', 'trips', 'trip_crates', 'trip_boxes',
                'prod_orders', 'prod_steps', 'purchase_orders', 'po_lines',
                'receipts', 'receipt_lines', 'suppliers', 'customers',
                'online_orders', 'online_order_lines', 'sales_returns', 'sales_return_lines',
                'purchase_returns', 'purchase_return_lines', 'journal_entries', 'journal_entry_lines',
                'expenses', 'cash_transfers', 'shift_expenses', 'locations', 'users',
            ];

            for ($i = 1; $i <= 500; $i++) {
                $action = $actions[array_rand($actions)];
                $table = $tables[array_rand($tables)];
                $recordId = rand(1, 100);
                
                $old = null;
                $new = null;
                
                if (in_array($action, ['update', 'approve', 'reject', 'close'])) {
                    $old = ['status' => 'pending', 'amount' => rand(100, 10000)];
                    $new = ['status' => $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'closed'), 'amount' => rand(100, 10000)];
                } elseif ($action === 'create') {
                    $new = ['name' => 'Record ' . $recordId, 'status' => 'active', 'created_at' => now()->toISOString()];
                } elseif ($action === 'delete') {
                    $old = ['name' => 'Record ' . $recordId, 'status' => 'active'];
                } elseif ($action === 'transfer') {
                    $old = ['location_id' => 'factory'];
                    $new = ['location_id' => 'roushdy'];
                }

                AuditLog::create([
                    'by' => $users[array_rand($users)],
                    'action' => $action,
                    'table_name' => $table,
                    'record_id' => $recordId,
                    'old' => $old,
                    'new' => $new,
                    'shift_id' => rand(1, 100) > 70 ? rand(1, 60) : null,
                    'at' => now()->subDays(rand(0, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                ]);
            }
        });
    }
}