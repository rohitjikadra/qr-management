<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedDomain extends Model
{
    protected $fillable = ['domain', 'reason'];
}
