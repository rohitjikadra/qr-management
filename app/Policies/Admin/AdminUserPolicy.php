<?php

namespace App\Policies\Admin;

use App\Enums\UserRole;
use App\Models\User;

class AdminUserPolicy
{
    public function changeRole(User $actor, User $target): bool
    {
        return $actor->role === UserRole::SuperAdmin;
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->role === UserRole::SuperAdmin && $actor->id !== $target->id;
    }

    public function impersonate(User $actor, User $target): bool
    {
        return $actor->role === UserRole::SuperAdmin
            && $actor->id !== $target->id
            && $target->role === UserRole::User
            && ! $target->isBanned();
    }

    public function ban(User $actor, User $target): bool
    {
        if (! $actor->isAdmin() || $actor->id === $target->id) {
            return false;
        }

        if ($actor->role !== UserRole::SuperAdmin && $target->isAdmin()) {
            return false;
        }

        return true;
    }
}
