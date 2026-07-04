<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NullPlatformUiContext;
use App\Contract\PlatformUiContext;
use App\Contract\PlatformUiContextProvider as PlatformUiContextProviderInterface;
use App\Contract\TelegramPlatformUiContext;
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

        $initData = $request->headers->get('X-Telegram-Init-Data')
            ?? $request->cookies->get('tma_init_data')
            ?? $request->query->get('initData');

        if ($initData) {
            return new TelegramPlatformUiContext($initData);
        }

        return new NullPlatformUiContext();
    }
}
