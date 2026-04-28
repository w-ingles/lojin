<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    public $timestamps = false;

    protected $fillable = ['email', 'token', 'code', 'data', 'attempts', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'data'       => 'encrypted',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function maxAttemptsReached(): bool
    {
        return $this->attempts >= 5;
    }
}