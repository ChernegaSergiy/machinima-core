<?php

declare(strict_types=1);

namespace App\Contract;

interface PlatformUiContextProvider
{
    public function getContext(): PlatformUiContext;
}
