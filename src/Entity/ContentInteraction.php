<?php

namespace Morfeditorial\MachinimaCoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'content_interactions')]
#[ORM\UniqueConstraint(name: 'unique_user_content_interaction', columns: ['user_id', 'content_id', 'interaction_type'])]
class ContentInteraction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Content::class)]
    #[ORM\JoinColumn(name: 'content_id', referencedColumnName: 'id', nullable: false)]
    private ?Content $content = null;

    #[ORM\Column(name: 'interaction_type', type: 'text')]
    private ?string $interactionType = null;

    #[ORM\Column(name: 'created_at', type: 'text', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?string $createdAt = null;

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

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getInteractionType(): ?string
    {
        return $this->interactionType;
    }

    public function setInteractionType(string $interactionType): self
    {
        $this->interactionType = $interactionType;

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
}
