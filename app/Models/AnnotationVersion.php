<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnotationVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'annotation_id',
        'image_id',
        'user_id',
        'version',
        'canvas_data',
        'edited_storage_path',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function annotation(): BelongsTo
    {
        return $this->belongsTo(ImageAnnotation::class, 'annotation_id');
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
