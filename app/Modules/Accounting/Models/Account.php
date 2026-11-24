<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'balance',
        'is_active',
        'location_id',
        'opening_balance',
        'allow_posting',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'allow_posting' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public static function getCategories(): array
    {
        return [
            'current_assets' => 'الأصول المتداولة',
            'fixed_assets' => 'الأصول الثابتة',
            'cash' => 'النقدية والصندوق',
            'bank' => 'الحسابات البنكية',
            'inventory' => 'المخزون',
            'receivables' => 'العملاء والمدينون',
            'payables' => 'الموردون والدائنون',
            'operating_expenses' => 'المصروفات التشغيلية',
            'sales_revenue' => 'إيرادات المبيعات',
            'cost_of_sales' => 'تكلفة المبيعات',
            'equity' => 'حقوق الملكية',
        ];
    }

    public function canPost(): bool
    {
        return !$this->is_header ?? true;
    }
}