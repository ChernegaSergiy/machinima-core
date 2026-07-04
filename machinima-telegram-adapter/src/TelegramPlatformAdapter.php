<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter;

use App\Contract\PlatformAdapterInterface;
use App\Contract\PlatformUiContext;
use App\Contract\NullPlatformUiContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.platform_adapter')]
final class TelegramPlatformAdapter implements PlatformAdapterInterface
{
    public function supports(Request ): bool
    {
        // Detect Telegram Web App request via standard parameters or headers
        return ->query->has('tg_webapp_url') || ->headers->has('x-telegram-webapp');
    }

    public function getContext(Request ): PlatformUiContext
    {
        // TODO: Populate real context (platform name, theme, init data, etc.)
        // For now, return a minimal null context implementation.
        return new NullPlatformUiContext();
    }
}
