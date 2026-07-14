<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Avatar;

class IdenticonAvatarProvider implements AvatarProviderInterface
{
    public function getAvatarUrl(int $userId): string
    {
        return 'https://api.dicebear.com/7.x/identicon/svg?seed='.$userId.'&backgroundColor=f0f0f0';
    }
}
