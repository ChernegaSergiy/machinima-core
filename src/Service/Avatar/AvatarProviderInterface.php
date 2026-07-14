<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Avatar;

interface AvatarProviderInterface
{
    /**
     * Gets the URL of the avatar for the given user ID.
     * Should return a URL or a fallback identifier (like 'default').
     */
    public function getAvatarUrl(int $userId): string;
}
