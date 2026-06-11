<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrDailyStat extends Model
{
    protected $fillable = [
        'qr_code_id',
        'date',
        'scans',
        'top_country',
        'top_device',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
