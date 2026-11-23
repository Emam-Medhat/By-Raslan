<?php

namespace App\Modules\Transfers\Models;

use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    public const STATUSES = ['transit', 'received'];

    protected $fillable = [
        'no', 'from_loc', 'to_loc', 'status', 'reason', 'note', 'sent_by', 'sent_at', 'recv_by', 'recv_at', 'trip_id',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'recv_at' => 'datetime',
        ];
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(TransferBox::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_loc');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_loc');
    }
}