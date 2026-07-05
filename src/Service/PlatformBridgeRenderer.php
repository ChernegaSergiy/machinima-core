<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PlatformAdapterInterface;
use App\Contract\PlatformUiContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Twig\Environment;

final class PlatformBridgeRenderer
{
    /**
     * @param iterable<PlatformAdapterInterface> $adapters
     */
    public function __construct(
        #[TaggedIterator('app.platform_adapter')]
        private iterable $adapters,
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

        foreach ($this->adapters as $adapter) {
            $templatePath = $adapter->getBridgeTemplatePath();
            if (null === $templatePath) {
                continue;
            }

            $context = $adapter->getContext($request);

            return $this->twig->render($templatePath, [
                'request' => $request,
                'context' => $context,
                'zero_click_url' => $adapter->getZeroClickLoginUrl(),
                'login_route' => $adapter->getLoginRouteName(),
            ]);
        }

        return '';
    }
}
