<?php

namespace App\Policies;

use App\Models\QrCode;
use App\Models\User;

class QrCodePolicy
{
    public function view(User $user, QrCode $qrCode): bool
    {
        return $qrCode->user_id === $user->id;
    }

    public function update(User $user, QrCode $qrCode): bool
    {
        return $qrCode->user_id === $user->id
            && ! $qrCode->admin_locked
            && ! $qrCode->frozen;
    }

    public function delete(User $user, QrCode $qrCode): bool
    {
        return $qrCode->user_id === $user->id;
    }
}
