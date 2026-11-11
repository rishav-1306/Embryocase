<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmbryoCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'embryo_number',
        'father_name',
        'mother_name',
        'collection_date',
        'collection_time',
        'total_days',
        'lab_notes',
        'additional_observations',
        'status',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'total_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(EmbryoImage::class, 'case_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'case_id');
    }

    /**
     * Generate next sequential Case ID: EMB-YYYY-XXXX
     */
    public static function generateCaseId(): string
    {
        $year = date('Y');
        $prefix = "EMB-{$year}-";

        $latestCase = self::withTrashed()
            ->where('case_id', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latestCase && preg_match('/EMB-\d{4}-(\d+)/', $latestCase->case_id, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf("EMB-%s-%04d", $year, $nextNumber);
    }
}
