<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NullPlatformUiContext;
use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider as PlatformUiContextProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provider that delegates to the first matching PlatformAdapter (tagged with `app.platform_adapter`).
 * If none matches, it falls back to NullPlatformUiContext.
 */
final class PlatformUiContextProvider implements PlatformUiContextProviderInterface
{
    private PlatformAdapterRegistry $registry;
    private RequestStack $requestStack;

    public function __construct(
        PlatformAdapterRegistry $registry,
        RequestStack $requestStack,
    ) {
        $this->registry = $registry;
        $this->requestStack = $requestStack;
    }

    public function getContext(): PlatformUiContext
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return new NullPlatformUiContext();
        }

        $adapter = $this->registry->findAdapter($request);
        if (null === $adapter) {
            return new NullPlatformUiContext();
        }

        return $adapter->getContext($request);
    }
}
