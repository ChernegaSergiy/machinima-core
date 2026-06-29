<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOrCreate(int $userId): User
    {
        $user = $this->find($userId);
        if (!$user) {
            $user = new User();
            $user->setId($userId);
            $this->getEntityManager()->persist($user);
        }
        return $user;
    }

    public function getCurrentPanel(int $userId): ?int
    {
        return $this->find($userId)?->getCurrentPanel();
    }

    public function setCurrentPanel(int $userId, ?int $panelId): void
    {
        $this->findOrCreate($userId)->setCurrentPanel($panelId);
        $this->getEntityManager()->flush();
    }

    public function getCurrentPage(int $userId): ?string
    {
        return $this->find($userId)?->getCurrentPage();
    }

    public function setCurrentPage(int $userId, ?string $page): void
    {
        $this->findOrCreate($userId)->setCurrentPage($page);
        $this->getEntityManager()->flush();
    }

    public function resetCurrentPage(int $userId): void
    {
        $user = $this->find($userId);
        if ($user) {
            $user->setCurrentPage(null);
            $this->getEntityManager()->flush();
        }
    }
}
