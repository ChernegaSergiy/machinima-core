<?php

declare(strict_types=1);

namespace App\Contract;

class IdentityAssertion
{
    public function __construct(
        private readonly string $providerName,
        private readonly string $providerSubjectId,
        private readonly ?string $displayName = null,
        private readonly ?string $avatarUrl = null,
        private readonly array $claims = [],
        private readonly ?\DateTimeImmutable $issuedAt = null,
        private readonly ?string $nonce = null,
    ) {
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getProviderSubjectId(): string
    {
        return $this->providerSubjectId;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getClaims(): array
    {
        return $this->claims;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }
}
