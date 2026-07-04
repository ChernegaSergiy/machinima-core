<?php

declare(strict_types=1);

namespace App\Twig;

use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider;
use Twig\Extension\RuntimeExtensionInterface;

class PlatformUiContextRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private PlatformUiContextProvider $provider,
    ) {
    }

    public function getContext(): PlatformUiContext
    {
        return $this->provider->getContext();
    }
}
