<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationLine extends Model
{
    public $timestamps = false;
    protected $fillable = ['reservation_id','item_id','qty','note'];
    protected function casts(): array { return ['qty' => 'decimal:3']; }
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
