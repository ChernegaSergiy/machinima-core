<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Media;

class DefaultMediaProvider implements MediaProviderInterface
{
    public function getMediaUrl(string $fileId): string
    {
        return 'default';
    }
}
