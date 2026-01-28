<?php

namespace App\Policies;

use App\Enums\ContentAccess;
use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Playlist $playlist): bool
    {
        $access = ContentAccess::from($playlist->access);

        return match ($access) {
            ContentAccess::PUBLIC, ContentAccess::UNLINKED => true,
            ContentAccess::CENTRALIENS => $user !== null,
            ContentAccess::PRIVATE => $user?->hasPermission('myclap.private') ?? false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manager.playlist');
    }

    public function update(User $user, Playlist $playlist): bool
    {
        return $user->hasPermission('manager.playlist');
    }

    public function delete(User $user, Playlist $playlist): bool
    {
        return $user->hasPermission('manager.playlist');
    }
}
