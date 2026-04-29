<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = ['tenant_id','name','email','cpf','phone','birth_date','password','role'];
    protected $hidden   = ['password','remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function tenant(): BelongsTo       { return $this->belongsTo(Tenant::class); }
    public function orders(): HasMany         { return $this->hasMany(Order::class); }
    public function commissioners(): HasMany  { return $this->hasMany(Commissioner::class); }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool      { return in_array($this->role, ['admin','super_admin']); }
    public function isUser(): bool       { return $this->role === 'user'; }

    // ── Completude de perfil para checkout ────────────────────────────────────
    public function isProfileComplete(): bool
    {
        return !empty($this->name) && !empty($this->email) && !empty($this->phone);
    }

    public function profileMissingFields(): array
    {
        $missing = [];
        if (empty($this->name))  $missing[] = 'nome';
        if (empty($this->phone)) $missing[] = 'telefone';
        return $missing;
    }
}
