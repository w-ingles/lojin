<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id','itemable_type','itemable_id','item_name','quantity','unit_price','total_price'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'total_price' => 'decimal:2'];
    }

    public function order(): BelongsTo  { return $this->belongsTo(Order::class); }
    public function itemable(): MorphTo { return $this->morphTo(); }
    public function tickets(): HasMany  { return $this->hasMany(Ticket::class); }
}
