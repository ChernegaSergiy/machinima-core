<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

interface PlatformUiContextProvider
{
    public function getContext(): PlatformUiContext;
}
