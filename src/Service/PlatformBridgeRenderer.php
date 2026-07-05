<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PlatformAdapterInterface;
use App\Contract\PlatformUiContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

final class PlatformBridgeRenderer
{
    public function __construct(
        private PlatformAdapterRegistry $registry,
        private RequestStack $requestStack,
        private Environment $twig,
    ) {
    }

    public function renderBridgeAssets(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return '';
        }

        $adapter = $this->registry->findAdapter($request);
        if (null === $adapter) {
            return '';
        }

        $templatePath = $adapter->getBridgeTemplatePath();
        if (null === $templatePath) {
            return '';
        }

        $context = $adapter->getContext($request);

        return $this->twig->render($templatePath, [
            'request' => $request,
            'context' => $context,
            'zero_click_url' => $adapter->getZeroClickLoginUrl(),
            'login_route' => $adapter->getLoginRouteName(),
        ]);
    }
}
