<?php

declare(strict_types=1);

namespace App\Contract;

interface PlatformUiContext
{
    public function isEmbedded(): bool;

    public function getPlatformName(): string;

    public function getTheme(): string;

    public function getInitData(): ?string;

    public function getUserId(): ?string;

    public function getBotLink(): ?string;

    public function getCapabilities(): array;
}
