<?php

declare(strict_types=1);

namespace App\Twig;

use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider;
use App\Service\PlatformAdapterRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Twig\Extension\RuntimeExtensionInterface;

class PlatformUiContextRuntime implements RuntimeExtensionInterface
{
    /** @var iterable<\App\Contract\SplashScreenInterface> */
    private iterable $splashScreens;

    public function __construct(
        private PlatformUiContextProvider $provider,
        private PlatformAdapterRegistry $registry,
        private Security $security,
        private RequestStack $requestStack,
        #[AutowireIterator('app.splash_screen')]
        iterable $splashScreens,
    ) {
        $this->splashScreens = $splashScreens;
    }

    public function getContext(): PlatformUiContext
    {
        return $this->provider->getContext();
    }

    /**
     * Bootstrap module paths for every registered adapter that declares one,
     * resolved to public URLs. Only relevant while there is no authenticated
     * user yet — once a session is authenticated, no adapter needs to try
     * bootstrapping again.
     *
     * This project doesn't use symfony/asset anywhere (it isn't even
     * installed) — every other asset in base.html.twig is a plain hardcoded
     * `/path` with a manual `?v=` cache-buster, so bootstrap/ui-hints module
     * paths follow the same convention rather than introducing a new
     * dependency for just this.
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
                $paths[] = $this->toPublicUrl($path);
            }
        }

        return $paths;
    }

    /**
     * UI-hints module path for the adapter owning the current authenticated
     * session, resolved to a public URL, or null if there isn't one.
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

        return null !== $path ? $this->toPublicUrl($path) : null;
    }

    private function toPublicUrl(string $path): string
    {
        return '/' . ltrim($path, '/') . '?v=' . time();
    }

    /**
     * @return list<array{platformName: string, templatePath: string}>
     */
    public function getSplashScreens(): array
    {
        $screens = [];
        foreach ($this->splashScreens as $splash) {
            $screens[] = [
                'platformName' => $splash->getPlatformName(),
                'templatePath' => $splash->getTemplatePath(),
                'displayCondition' => $splash->getDisplayCondition(),
            ];
        }

        return $screens;
    }
}
