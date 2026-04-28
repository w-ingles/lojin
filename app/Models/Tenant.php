<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name','slug','email','phone','university_id','description','logo','plan','is_active','settings'];

    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function university(): BelongsTo { return $this->belongsTo(University::class); }

    public function users(): HasMany       { return $this->hasMany(User::class); }
    public function events(): HasMany      { return $this->hasMany(Event::class); }
    public function products(): HasMany    { return $this->hasMany(Product::class); }
    public function orders(): HasMany      { return $this->hasMany(Order::class); }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
