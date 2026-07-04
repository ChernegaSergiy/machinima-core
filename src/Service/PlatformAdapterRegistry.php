<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PlatformAdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Registry that collects all platform adapters (tagged with `app.platform_adapter`).
 * It can find the first adapter that supports the current request.
 */
final class PlatformAdapterRegistry
{
    /** @var iterable<PlatformAdapterInterface> */
    private iterable $adapters;

    public function __construct(
        #[TaggedIterator('app.platform_adapter')]
        iterable $adapters = [],
    ) {
        $this->adapters = $adapters;
    }

    /**
     * Returns the first adapter that claims to support the given request,
     * or null if none matches.
     */
    public function findAdapter(Request $request): ?PlatformAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($request)) {
                return $adapter;
            }
        }

        return null;
    }
}
