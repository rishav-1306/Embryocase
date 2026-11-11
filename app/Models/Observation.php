<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observation extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'image_id',
        'user_id',
        'day_number',
        'title',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
        ];
    }

    public function embryoCase(): BelongsTo
    {
        return $this->belongsTo(EmbryoCase::class, 'case_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(EmbryoImage::class, 'image_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

