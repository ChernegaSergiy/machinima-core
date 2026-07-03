<?php

namespace App\Service\Avatar;

class DefaultAvatarProvider implements AvatarProviderInterface
{
    public function getAvatarUrl(int $userId): string
    {
        return 'https://api.dicebear.com/7.x/identicon/svg?seed=' . $userId;
    }
}
