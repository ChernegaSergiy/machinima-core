<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NullPlatformUiContext;
use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider as PlatformUiContextProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PlatformUiContextProvider implements PlatformUiContextProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function getContext(): PlatformUiContext
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return new NullPlatformUiContext();
        }

        return new NullPlatformUiContext();
    }
}
