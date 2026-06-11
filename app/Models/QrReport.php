<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrReport extends Model
{
    protected $fillable = [
        'qr_code_id',
        'reason',
        'reporter_ip_hash',
        'status',
    ];

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
