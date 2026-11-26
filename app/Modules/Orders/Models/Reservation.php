<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'converted', 'cancelled'];

    protected $fillable = [
        'no','location_id','customer_id','customer_name','customer_phone','customer_address',
        'scheduled_for','status','notes','created_by','confirmed_by','confirmed_at','online_order_id','converted_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'confirmed_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo { return $this->belongsTo(Location::class, 'location_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function lines(): HasMany { return $this->hasMany(ReservationLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function confirmer(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
}
