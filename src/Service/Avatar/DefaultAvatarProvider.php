<?php

namespace App\Service\Avatar;

class DefaultAvatarProvider implements AvatarProviderInterface
{
    public function getAvatarUrl(int $userId): string
    {
        return 'default';
    }
}
