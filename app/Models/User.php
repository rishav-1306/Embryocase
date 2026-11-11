<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'special_key_hash',
        'last_login_at',
        'last_login_ip',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'special_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEmbryologist(): bool
    {
        return $this->role === 'embryologist';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function verifySpecialKey(string $plainKey): bool
    {
        if (empty($this->special_key_hash)) {
            return false;
        }
        return Hash::check($plainKey, $this->special_key_hash);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdEmbryologists(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function embryoCases(): HasMany
    {
        return $this->hasMany(EmbryoCase::class, 'user_id');
    }

    public function uploadedImages(): HasMany
    {
        return $this->hasMany(EmbryoImage::class, 'user_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    public function specialKeyHistory(): HasMany
    {
        return $this->hasMany(SpecialKeyHistory::class, 'user_id');
    }
}
