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
        $telegramId = (string) ($telegramUser['id'] ?? '');

        if (!$telegramId) {
            return; // Safety check
        }

        $identityRepo = $this->entityManager->getRepository(\App\Entity\UserIdentity::class);
        $identity = $identityRepo->findOneBy([
            'providerName' => 'telegram',
            'providerId' => $telegramId,
        ]);

        $needsFlush = false;

        if ($identity) {
            $user = $identity->getUser();
            // Optional: update provider data if changed
            $identity->setProviderData($telegramUser);
        } else {
            $user = new User();
            $this->entityManager->persist($user);

            $identity = new \App\Entity\UserIdentity();
            $identity->setUser($user);
            $identity->setProviderName('telegram');
            $identity->setProviderId($telegramId);
            $identity->setProviderData($telegramUser);
            $this->entityManager->persist($identity);

            $needsFlush = true;
        }

        $authorRepository = $this->entityManager->getRepository(Author::class);
        $author = $authorRepository->findOneBy(['user' => $user]);

        if (!$author) {
            $author = new Author();
            $author->setUser($user);

            // Build a default name from Telegram data
            $nameParts = array_filter([$telegramUser['first_name'] ?? '', $telegramUser['last_name'] ?? '']);
            $name = !empty($nameParts) ? implode(' ', $nameParts) : 'Користувач #'.$telegramId;

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
