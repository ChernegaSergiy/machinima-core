<?php

declare(strict_types=1);

namespace App\Twig;

use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider;
use App\Service\PlatformAdapterRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class PlatformUiContextRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private PlatformUiContextProvider $provider,
        private PlatformAdapterRegistry $registry,
        private Security $security,
        private RequestStack $requestStack,
        private Packages $packages,
    ) {
    }

    public function getContext(): PlatformUiContext
    {
        return $this->provider->getContext();
    }

    /**
     * Resolved (asset-URL) bootstrap module paths for every registered
     * adapter that declares one. Only relevant while there is no
     * authenticated user yet — once a session is authenticated, no adapter
     * needs to try bootstrapping again.
     *
     * @return list<string>
     */
    public function getBootstrapModulePaths(): array
    {
        if (null !== $this->security->getUser()) {
            return [];
        }

        $paths = [];
        foreach ($this->registry->all() as $adapter) {
            $path = $adapter->getBootstrapModulePath();
            if (null !== $path) {
                $paths[] = $this->packages->getUrl($path);
            }
        }

        return $paths;
    }

    /**
     * Resolved (asset-URL) UI-hints module path for the adapter owning the
     * current authenticated session, or null if there isn't one.
     */
    public function getUiHintsModulePath(): ?string
    {
        if (null === $this->security->getUser()) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $providerName = $request?->hasSession() === true ? $request->getSession()->get('active_platform_provider') : null;
        if (!is_string($providerName)) {
            return null;
        }

        $adapter = $this->registry->findByPlatformName($providerName);
        $path = $adapter?->getUiHintsModulePath();

        return null !== $path ? $this->packages->getUrl($path) : null;
    }
}
