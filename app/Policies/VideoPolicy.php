<?php

namespace App\Policies;

use App\Enums\ContentAccess;
use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Video $video): bool
    {
        $access = ContentAccess::from($video->access);

        return match ($access) {
            ContentAccess::PUBLIC, ContentAccess::UNLINKED => true,
            ContentAccess::CENTRALIENS => $user !== null,
            ContentAccess::PRIVATE => $user?->hasPermission('myclap.private') ?? false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manager.video.upload');
    }

    public function update(User $user, Video $video): bool
    {
        if ($user->hasPermission('manager.video.manage')) {
            return true;
        }

        // User can edit their own videos
        return $video->uploaded_by === $user->username && $user->hasPermission('manager.video.upload');
    }

    public function delete(User $user, Video $video): bool
    {
        return $user->hasPermission('manager.video.manage');
    }
}
