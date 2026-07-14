<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Media;

interface MediaProviderInterface
{
    /**
     * Gets the URL of the media for the given file ID.
     * Should return a URL or a fallback identifier (like 'default').
     */
    public function getMediaUrl(string $fileId): string;
}
