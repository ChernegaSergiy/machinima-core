<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Repository;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Entity\UserState;
use Morfeditorial\MachinimaCoreBundle\Event\UserStateChangedEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserStateRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($registry, UserState::class);
    }

    public function get(int $userId, string $key = 'default'): mixed
    {
        $user = $this->getEntityManager()->find(User::class, $userId);
        if (!$user) {
            return null;
        }

        $state = $this->findOneBy(['user' => $user, 'stateKey' => $key]);

        return $state ? json_decode($state->getStateValue(), true) : null;
    }

    public function set(int $userId, mixed $value, string $key = 'default'): void
    {
        $em = $this->getEntityManager();
        $user = $em->find(User::class, $userId);
        if (!$user) {
            $user = new User();
            $user->setId($userId);
            $em->persist($user);
        }

        $state = $this->findOneBy(['user' => $user, 'stateKey' => $key]);
        if (!$state) {
            $state = new UserState();
            $state->setUser($user);
            $state->setStateKey($key);
            $em->persist($state);
        }
        $state->setStateValue(json_encode($value));
        $em->flush();

        $this->dispatcher->dispatch(new UserStateChangedEvent($user, $key, $value));
    }

    public function clear(int $userId, ?string $key = null): void
    {
        $em = $this->getEntityManager();
        $user = $em->find(User::class, $userId);
        if (!$user) {
            return;
        }

        if ($key) {
            $state = $this->findOneBy(['user' => $user, 'stateKey' => $key]);
            if ($state) {
                $em->remove($state);
            }
        } else {
            foreach ($this->findBy(['user' => $user]) as $state) {
                $em->remove($state);
            }
        }
        $em->flush();
    }
}
