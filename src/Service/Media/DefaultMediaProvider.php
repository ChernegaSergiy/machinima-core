<?php

namespace App\Service\Media;

class DefaultMediaProvider implements MediaProviderInterface
{
    public function getMediaUrl(string $fileId): string
    {
        return 'default';
    }
}
