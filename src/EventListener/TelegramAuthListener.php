<?php

namespace App\EventListener;

use App\Entity\Author;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Morfeditorial\TelegramBotBundle\Event\TelegramUserAuthenticatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TelegramUserAuthenticatedEvent::class)]
class TelegramAuthListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(TelegramUserAuthenticatedEvent $event): void
    {
        $telegramUser = $event->getTelegramUserData();
        $userId = $telegramUser['id'] ?? 0;

        if (!$userId) {
            return; // Safety check
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($userId);

        $needsFlush = false;

        if (!$user) {
            $user = new User();
            $user->setId($userId);
            $this->entityManager->persist($user);
            $needsFlush = true;
        }

        // Also ensure an Author record exists for this user
        $authorRepository = $this->entityManager->getRepository(Author::class);
        $author = $authorRepository->findOneBy(['telegramUserId' => $userId]);

        if (!$author) {
            $author = new Author();
            $author->setTelegramUserId($userId);

            // Build a default name from Telegram data
            $nameParts = array_filter([$telegramUser['first_name'] ?? '', $telegramUser['last_name'] ?? '']);
            $name = !empty($nameParts) ? implode(' ', $nameParts) : 'Користувач #'.$userId;

            $author->setName($name);
            $author->setState('active');

            $this->entityManager->persist($author);
            $needsFlush = true;
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }

        $event->setUser($user);
    }
}
