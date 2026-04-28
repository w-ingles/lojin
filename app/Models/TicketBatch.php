<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'event_id','tenant_id','name','description','price','quantity',
        'sold','is_active','available_from','available_until','max_per_order',
    ];

    protected $appends = ['available'];

    protected function casts(): array
    {
        return [
            'price'           => 'decimal:2',
            'is_active'       => 'boolean',
            'available_from'  => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function event(): BelongsTo  { return $this->belongsTo(Event::class); }
    public function tickets(): HasMany  { return $this->hasMany(Ticket::class); }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->sold);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->available <= 0) return false;
        if ($this->available_from && now()->lt($this->available_from)) return false;
        if ($this->available_until && now()->gt($this->available_until)) return false;
        return true;
    }
}
