<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_data')]
class User implements UserInterface
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
    public function getUserRoles(): Collection
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [];
        // Map custom database roles to Symfony's expected ROLE_ format, including hierarchy
        foreach ($this->roles as $roleEntity) {
            $this->addRoleAndChildren($roleEntity, $roles);
        }

        if ($this->legacyRole) {
            $roleName = strtoupper($this->legacyRole);
            if (!str_starts_with($roleName, 'ROLE_')) {
                $roleName = 'ROLE_'.$roleName;
            }
            $roles[] = $roleName;
        }

        // Guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    private function addRoleAndChildren(Role $role, array &$roles): void
    {
        $roleName = $role->getRoleName();

        if (!in_array($roleName, $roles, true)) {
            $roles[] = $roleName;
            foreach ($role->getChildren() as $childRole) {
                $this->addRoleAndChildren($childRole, $roles);
            }
        }
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}
