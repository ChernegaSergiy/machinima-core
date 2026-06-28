<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'roles')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'role_name', type: 'text', unique: true)]
    private ?string $roleName = null;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'role_hierarchy')]
    #[ORM\JoinColumn(name: 'parent_role_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'child_role_id', referencedColumnName: 'id')]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setRoleName(string $roleName): self
    {
        $this->roleName = $roleName;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Role $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
        }

        return $this;
    }

    public function removeChild(Role $child): self
    {
        $this->children->removeElement($child);

        return $this;
    }
}
