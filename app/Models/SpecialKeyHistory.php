<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialKeyHistory extends Model
{
    use HasFactory;

    protected $table = 'special_key_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'key_hash',
        'action',
        'created_by',
        'revoked_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

