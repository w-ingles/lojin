<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['tenant_id','product_category_id','name','description','price','stock','image','active'];
    protected $appends  = ['image_url'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'active' => 'boolean'];
    }

    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
