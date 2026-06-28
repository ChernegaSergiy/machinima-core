<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'authors')]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $biography = null;

    #[ORM\Column(name: 'channel_link', type: 'text', nullable: true)]
    private ?string $channelLink = null;

    #[ORM\Column(name: 'created_at', type: 'text', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?string $createdAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $state = null;

    #[ORM\Column(name: 'telegram_user_id', type: 'integer', nullable: true)]
    private ?int $telegramUserId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): self
    {
        $this->biography = $biography;

        return $this;
    }

    public function getChannelLink(): ?string
    {
        return $this->channelLink;
    }

    public function setChannelLink(?string $channelLink): self
    {
        $this->channelLink = $channelLink;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getTelegramUserId(): ?int
    {
        return $this->telegramUserId;
    }

    public function setTelegramUserId(?int $telegramUserId): self
    {
        $this->telegramUserId = $telegramUserId;

        return $this;
    }
}
