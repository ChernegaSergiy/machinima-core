<?php

declare(strict_types=1);

namespace App\Contract;

class NullPlatformUiContext implements PlatformUiContext
{
    public function isEmbedded(): bool
    {
        return false;
    }

    public function getPlatformName(): string
    {
        return 'web';
    }

    public function getTheme(): string
    {
        return 'light';
    }

    public function getUserId(): ?string
    {
        return null;
    }

    public function getBotLink(): ?string
    {
        return null;
    }

    public function getCapabilities(): array
    {
        return [];
    }
}
