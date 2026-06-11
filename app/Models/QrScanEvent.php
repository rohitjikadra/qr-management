<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScanEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'qr_code_id',
        'country',
        'city',
        'device_type',
        'os',
        'browser',
        'referrer',
        'ip_hash',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
