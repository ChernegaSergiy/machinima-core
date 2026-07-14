<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\User;

use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function getRoles(int $userId): ?array
    {
        $user = $this->em->getRepository(\Morfeditorial\MachinimaCoreBundle\Entity\User::class)->find($userId);
        if (!$user) {
            return null;
        }

        $roles = $user->getRoles();
        $isModerator = in_array('ROLE_MODERATOR', $roles, true) || in_array('ROLE_ADMIN', $roles, true);

        return ['roles' => $roles, 'is_moderator' => $isModerator];
    }
}
