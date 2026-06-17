<?php

namespace App\Models;

use App\Enums\QrStatus;
use App\Enums\QrType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class QrCode extends Model
{
    use SoftDeletes;

    public const CACHE_PREFIX = 'qr:';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'type',
        'content',
        'destination_url',
        'is_dynamic',
        'status',
        'admin_locked',
        'design_options',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => QrType::class,
            'content' => 'array',
            'is_dynamic' => 'boolean',
            'status' => QrStatus::class,
            'admin_locked' => 'boolean',
            'frozen' => 'boolean',
            'design_options' => 'array',
            'last_scanned_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Event-based cache invalidation for the redirect hot path
        static::saved(fn (self $qr) => $qr->invalidateRedirectCache());
        static::deleted(fn (self $qr) => $qr->invalidateRedirectCache());
    }

    public function invalidateRedirectCache(): void
    {
        if ($this->slug) {
            Cache::forget(self::CACHE_PREFIX.$this->slug);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(QrScanEvent::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(QrDailyStat::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(QrReport::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function redirectUrl(): ?string
    {
        return $this->slug ? url('/q/'.$this->slug) : null;
    }
}
