<?php

declare(strict_types=1);

namespace App\Twig;

use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider;
use App\Service\PlatformAdapterRegistry;
use Twig\Extension\RuntimeExtensionInterface;

class PlatformUiContextRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private PlatformUiContextProvider $provider,
        private PlatformAdapterRegistry $registry,
    ) {
    }

    public function getContext(): PlatformUiContext
    {
        return $this->provider->getContext();
    }

    /**
     * Bridge template paths for every registered adapter that declares one.
     * Rendered unconditionally on every page — see
     * PlatformAdapterInterface::getBridgeTemplatePath() for the rationale.
     *
     * @return list<string>
     */
    public function getBridgeTemplatePaths(): array
    {
        $paths = [];

        foreach ($this->registry->all() as $adapter) {
            $path = $adapter->getBridgeTemplatePath();
            if (null !== $path) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
