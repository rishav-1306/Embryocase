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
     * Get soft-deleted embryo images retained within the 24-hour window
     */
    public function trashedImages24h(): HasMany
    {
        return $this->hasMany(EmbryoImage::class, 'case_id')
            ->onlyTrashed()
            ->where('deleted_at', '>=', now()->subHours(24))
            ->orderBy('deleted_at', 'desc');
    }

    /**
     * Get the developmental day that corresponds to today's date
     * based on collection_date (Day 1)
     *
     * @return int|null
     */
    public function getTodayDevelopmentDayAttribute(): ?int
    {
        if (!$this->collection_date) {
            return null;
        }

        $days = (int) $this->collection_date->startOfDay()->diffInDays(now()->startOfDay(), false);
        if ($days < 0) {
            return null; // collection date is in the future
        }

        $todayDay = $days + 1;
        if ($todayDay > $this->total_days) {
            return null; // tracking window exceeded
        }

        return $todayDay;
    }

    /**
     * Check if today's scheduled image acquisition has not been uploaded yet
     */
    public function isTodayImageMissing(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $todayDay = $this->today_development_day;
        if (!$todayDay) {
            return false;
        }

        return !$this->images()->where('day_number', $todayDay)->exists();
    }

    /**
     * Get all active cases where today's scheduled acquisition image is missing
     */
    public static function getCasesWithMissingTodayImage(?int $userId = null)
    {
        $query = self::where('status', 'in_progress')->with(['images', 'user']);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->filter(function ($case) {
            return $case->isTodayImageMissing();
        });
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

