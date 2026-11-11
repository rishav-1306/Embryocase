<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmbryoImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'user_id',
        'day_number',
        'filename',
        'original_name',
        'storage_path',
        'capture_time',
        'capture_date',
        'file_size',
        'mime_type',
        'width',
        'height',
        'has_edit',
        'edited_filename',
        'edited_storage_path',
        'annotation_json_path',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'has_edit' => 'boolean',
            'capture_date' => 'date',
        ];
    }

    public function embryoCase(): BelongsTo
    {
        return $this->belongsTo(EmbryoCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function annotation(): HasOne
    {
        return $this->hasOne(ImageAnnotation::class, 'image_id');
    }

    public function annotationVersions(): HasMany
    {
        return $this->hasMany(AnnotationVersion::class, 'image_id')->orderBy('version', 'desc');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'image_id');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
