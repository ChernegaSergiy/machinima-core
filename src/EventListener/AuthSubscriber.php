<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Role;
use App\Entity\User;
use App\Event\UserAuthenticatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UserAuthenticatedEvent::class)]
class AuthSubscriber
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(UserAuthenticatedEvent $event): void
    {
        $assertion = $event->getAssertion();
        $needsFlush = false;

        $identityRepo = $this->entityManager->getRepository(\App\Entity\UserIdentity::class);
        $identity = $identityRepo->findOneBy([
            'providerName' => $assertion->getProviderName(),
            'providerId' => $assertion->getProviderSubjectId(),
        ]);

        if ($identity) {
            $user = $identity->getUser();
        } else {
            $user = new User();
            $user->setUserState('active');
            $this->entityManager->persist($user);

            $identity = new \App\Entity\UserIdentity();
            $identity->setUser($user);
            $identity->setProviderName($assertion->getProviderName());
            $identity->setProviderId($assertion->getProviderSubjectId());
            $identity->setProviderData($assertion->getClaims());
            $this->entityManager->persist($identity);

            $roleRepo = $this->entityManager->getRepository(Role::class);
            $role = $roleRepo->findOneBy(['roleName' => 'ROLE_USER']);

            if (null === $role) {
                $role = new Role();
                $role->setRoleName('ROLE_USER');
                $this->entityManager->persist($role);
            }

            $user->addRole($role);

            $needsFlush = true;
        }

        $displayName = $assertion->getDisplayName();

        if ($displayName && !$user->getDisplayName()) {
            $user->setDisplayName($displayName);
            $needsFlush = true;
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }

        $event->setUser($user);
    }
}
