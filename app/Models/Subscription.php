<?php

namespace App\Models;

use App\Enums\RenewalType;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'gateway',
        'renewal_type',
        'gateway_subscription_id',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'status',
        'is_complimentary',
        'admin_note',
        'granted_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => SubscriptionStatus::class,
            'renewal_type' => RenewalType::class,
            'is_complimentary' => 'boolean',
        ];
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
