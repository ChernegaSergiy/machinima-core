<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

interface IdentityProviderMetadataProvider
{
    /**
     * @return array{label: string, icon: string, login_url?: string, description?: string}
     */
    public function getMetadata(): array;
}
