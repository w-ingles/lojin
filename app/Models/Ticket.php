<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use BelongsToTenant;

    protected $fillable = ['ticket_batch_id','tenant_id','user_id','order_item_id','code','status','used_at'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->code ??= strtoupper(Str::random(12)));
    }

    public function batch(): BelongsTo     { return $this->belongsTo(TicketBatch::class, 'ticket_batch_id'); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
