<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service;

use Morfeditorial\MachinimaCoreBundle\Contract\NullPlatformUiContext;
use Morfeditorial\MachinimaCoreBundle\Contract\PlatformUiContext;
use Morfeditorial\MachinimaCoreBundle\Contract\PlatformUiContextProvider as PlatformUiContextProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provider that resolves the PlatformUiContext for the CURRENT SESSION —
 * not the current request. Which adapter owns a session is a plain fact
 * ("active_platform_provider") written once by AuthBootstrapController the
 * moment a zero-click login succeeds; there is no per-request sniffing.
 *
 * Falls back to NullPlatformUiContext for anonymous sessions, sessions with
 * no recorded platform (e.g. logged in via a plain OAuth button), or an
 * unrecognized platform name.
 */
final class PlatformUiContextProvider implements PlatformUiContextProviderInterface
{
    public function __construct(
        private PlatformAdapterRegistry $registry,
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    public function getContext(): PlatformUiContext
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || null === $this->security->getUser()) {
            return new NullPlatformUiContext();
        }

        $providerName = $request->hasSession() ? $request->getSession()->get('active_platform_provider') : null;
        if (!is_string($providerName)) {
            return new NullPlatformUiContext();
        }

        $adapter = $this->registry->findByPlatformName($providerName);
        if (null === $adapter) {
            return new NullPlatformUiContext();
        }

        return $adapter->getUiContext($request);
    }
}
