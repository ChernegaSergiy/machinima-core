<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_states')]
class UserState
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\Column(name: 'state_key', type: 'text')]
    private ?string $stateKey = null;

    #[ORM\Column(name: 'state_value', type: 'text', nullable: true)]
    private ?string $stateValue = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStateKey(): ?string
    {
        return $this->stateKey;
    }

    public function setStateKey(string $stateKey): self
    {
        $this->stateKey = $stateKey;

        return $this;
    }

    public function getStateValue(): ?string
    {
        return $this->stateValue;
    }

    public function setStateValue(?string $stateValue): self
    {
        $this->stateValue = $stateValue;

        return $this;
    }
}
