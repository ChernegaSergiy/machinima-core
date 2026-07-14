<?php

namespace Morfeditorial\MachinimaCoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_identities')]
#[ORM\UniqueConstraint(name: 'unique_provider_identity', columns: ['provider_name', 'provider_id'])]
class UserIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $providerName = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $providerId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $providerData = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): self
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(string $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getProviderData(): ?array
    {
        return $this->providerData;
    }

    public function setProviderData(?array $providerData): self
    {
        $this->providerData = $providerData;

        return $this;
    }
}
