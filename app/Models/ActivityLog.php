<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'case_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper to log activities safely without sensitive information
     */
    public static function record(?int $userId, string $action, ?string $caseId = null, array $metadata = []): self
    {
        // Sanitize any accidentally passed sensitive keys
        $cleanMetadata = array_diff_key($metadata, array_flip([
            'password', 'password_confirmation', 'otp', 'special_key', 'special_key_hash', 'token'
        ]));

        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'case_id' => $caseId,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'metadata' => $cleanMetadata,
            'created_at' => now(),
        ]);
    }
}

