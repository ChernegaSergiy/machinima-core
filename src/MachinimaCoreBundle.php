<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MachinimaCoreBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
