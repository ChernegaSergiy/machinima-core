<?php

namespace App\Service\Media;

interface MediaProviderInterface
{
    /**
     * Gets the URL of the media for the given file ID.
     * Should return a URL or a fallback identifier (like 'default').
     */
    public function getMediaUrl(string $fileId): string;
}
