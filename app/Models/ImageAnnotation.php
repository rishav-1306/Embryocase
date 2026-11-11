<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageAnnotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_id',
        'user_id',
        'canvas_data',
        'canvas_width',
        'canvas_height',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'canvas_width' => 'integer',
            'canvas_height' => 'integer',
            'version' => 'integer',
        ];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(EmbryoImage::class, 'image_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AnnotationVersion::class, 'annotation_id')->orderBy('version', 'desc');
    }
}

