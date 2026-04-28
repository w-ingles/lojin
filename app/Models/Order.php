<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','user_id','commissioner_id','customer_name','customer_email','customer_phone','customer_cpf',
        'subtotal','total','status','payment_method','payment_id','paid_at','notes',
    ];

    protected $appends = ['customer_cpf_masked'];

    protected function casts(): array
    {
        return [
            'subtotal'     => 'decimal:2',
            'total'        => 'decimal:2',
            'paid_at'      => 'datetime',
            'customer_cpf' => 'encrypted',
        ];
    }

    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function commissioner(): BelongsTo { return $this->belongsTo(Commissioner::class); }
    public function items(): HasMany          { return $this->hasMany(OrderItem::class); }
    public function tickets(): HasMany        { return $this->hasManyThrough(Ticket::class, OrderItem::class, 'order_id', 'order_item_id'); }

    public function getCustomerCpfMaskedAttribute(): ?string
    {
        if (!$this->customer_cpf) return null;
        $cpf = preg_replace('/\D/', '', $this->customer_cpf);
        return '***.***.***-' . substr($cpf, -2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Pendente',
            'paid'      => 'Pago',
            'cancelled' => 'Cancelado',
            'refunded'  => 'Reembolsado',
            default     => $this->status,
        };
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid', 'paid_at' => now()]);
        $this->items->each(fn ($item) => $item->tickets()->update(['status' => 'paid']));
    }
}
