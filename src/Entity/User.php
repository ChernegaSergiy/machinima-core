<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_data')]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'user_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_state', type: 'text', nullable: true)]
    private ?string $userState = null;

    #[ORM\Column(name: 'current_panel', type: 'integer', nullable: true)]
    private ?int $currentPanel = null;

    #[ORM\Column(name: 'current_page', type: 'text', nullable: true)]
    private ?string $currentPage = null;

    #[ORM\Column(name: 'role', type: 'text', nullable: true)]
    private ?string $legacyRole = null;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'user_roles')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id')]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUserState(): ?string
    {
        return $this->userState;
    }

    public function setUserState(?string $userState): self
    {
        $this->userState = $userState;

        return $this;
    }

    public function getCurrentPanel(): ?int
    {
        return $this->currentPanel;
    }

    public function setCurrentPanel(?int $currentPanel): self
    {
        $this->currentPanel = $currentPanel;

        return $this;
    }

    public function getCurrentPage(): ?string
    {
        return $this->currentPage;
    }

    public function setCurrentPage(?string $currentPage): self
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function getLegacyRole(): ?string
    {
        return $this->legacyRole;
    }

    public function setLegacyRole(?string $legacyRole): self
    {
        $this->legacyRole = $legacyRole;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);

        return $this;
    }
}
