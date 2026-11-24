<?php

namespace App\Modules\Settings\Database\Seeders;

use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            'company_name' => 'BY RASLAN',
            'company_phone' => '01000000000',
            'company_email' => 'info@byraslan.com',
            'company_address' => 'الإسكندرية، مصر',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'date_format' => 'Y-m-d',
            'datetime_format' => 'Y-m-d H:i:s',
            
            // POS Settings
            'pos_auto_print' => 'true',
            'pos_receipt_footer' => 'شكراً لتعاملكم مع BY RASLAN',
            'pos_default_printer' => 'thermal_58mm',
            'pos_cash_drawer_code' => 'OPEN',
            
            // Inventory Settings
            'stock_alert_threshold' => '10',
            'auto_create_transfer' => 'false',
            'allow_negative_stock' => 'false',
            'default_unit' => 'كرتونة',
            
            // Pricing
            'price_precision' => '2',
            'default_channel' => 'direct',
            'allow_price_override' => 'true',
            'price_override_permission' => 'prices.edit',
            
            // Production
            'prod_batch_tracking' => 'true',
            'prod_waste_percentage' => '5',
            'prod_auto_complete_steps' => 'false',
            
            // Purchasing
            'po_auto_receive' => 'false',
            'receipt_temp_max' => '8',
            'receipt_qty_tolerance' => '0.05',
            'override_approval_required' => 'true',
            
            // Sales
            'max_discount_percentage' => '20',
            'discount_approval_required' => 'true',
            'cash_payment_limit' => '50000',
            'card_payment_enabled' => 'true',
            
            // Shifts
            'shift_auto_close' => 'false',
            'shift_cash_count_required' => 'true',
            'shift_expense_approval' => 'true',
            'carryover_enabled' => 'true',
            
            // Trips
            'trip_auto_assign_boxes' => 'true',
            'trip_crate_capacity' => '50',
            'trip_driver_tracking' => 'true',
            
            // Accounting
            'fiscal_year_start' => '01-01',
            'chart_of_accounts_standard' => 'egyptian',
            'auto_post_sales' => 'true',
            'auto_post_purchases' => 'true',
            'auto_post_inventory' => 'true',
            
            // Notifications
            'push_notifications_enabled' => 'true',
            'email_notifications_enabled' => 'false',
            'sms_notifications_enabled' => 'false',
            'low_stock_alert_enabled' => 'true',
            
            // Integrations
            'expo_project_id' => '',
            'expo_access_token' => '',
            'supabase_url' => '',
            'supabase_anon_key' => '',
            
            // Reports
            'report_auto_generate' => 'false',
            'report_retention_days' => '365',
            'default_report_format' => 'pdf',
            
            // Security
            'session_timeout' => '120',
            'max_login_attempts' => '5',
            'lockout_duration' => '15',
            'password_min_length' => '8',
            
            // API
            'api_rate_limit' => '60',
            'api_token_expiry' => '3600',
            'webhook_secret' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}