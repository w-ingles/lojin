<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id','name','description','location','address',
        'starts_at','ends_at','banner','status','minimum_age',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function tenant(): BelongsTo          { return $this->belongsTo(Tenant::class); }
    public function ticketBatches(): HasMany      { return $this->hasMany(TicketBatch::class); }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner ? asset('storage/' . $this->banner) : null;
    }

    public function getTotalCapacityAttribute(): int
    {
        return $this->ticketBatches->sum('quantity');
    }

    public function getTotalSoldAttribute(): int
    {
        return $this->ticketBatches->sum('sold');
    }

    public function scopeActive($q)   { return $q->where('status', 'active'); }
    public function scopeUpcoming($q) { return $q->where('starts_at', '>', now()); }
}
