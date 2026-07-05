<?php

declare(strict_types=1);

namespace App\Contract;

interface IdentityProviderMetadataProvider
{
    /**
     * @return array{label: string, icon: string, description?: string}
     */
    public function getMetadata(): array;
}
