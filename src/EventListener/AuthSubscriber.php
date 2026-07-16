<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\EventListener;

use Morfeditorial\MachinimaCoreBundle\Entity\Role;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Event\UserAuthenticatedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\UserRegisteredEvent;
use Morfeditorial\MachinimaCoreBundle\Event\UserProfileUpdatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEventListener(event: UserAuthenticatedEvent::class)]
class AuthSubscriber
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(UserAuthenticatedEvent $event): void
    {
        $assertion = $event->getAssertion();
        $needsFlush = false;
        $isNewUser = false;

        $identityRepo = $this->entityManager->getRepository(\Morfeditorial\MachinimaCoreBundle\Entity\UserIdentity::class);
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

            $identity = new \Morfeditorial\MachinimaCoreBundle\Entity\UserIdentity();
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
            $isNewUser = true;
        }

        $displayName = $assertion->getDisplayName();
        $profileUpdated = false;

        if ($displayName && !$user->getDisplayName()) {
            $user->setDisplayName($displayName);
            $needsFlush = true;
            $profileUpdated = true;
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }

        if ($isNewUser) {
            $this->dispatcher->dispatch(new UserRegisteredEvent($user));
        }

        if ($profileUpdated) {
            $this->dispatcher->dispatch(new UserProfileUpdatedEvent($user, ['displayName']));
        }

        $event->setUser($user);
    }
}
