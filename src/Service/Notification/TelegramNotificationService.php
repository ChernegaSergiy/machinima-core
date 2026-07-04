<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Contract\NotificationChannelPort;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class TelegramNotificationService implements NotificationChannelPort
{
    public function __construct(
        private EntityManagerInterface $em,
        private ChatterInterface $chatter,
    ) {
    }

    public function send(User $user, string $message, array $options = []): void
    {
        $identity = $this->em->getRepository(\App\Entity\UserIdentity::class)->findOneBy([
            'user' => $user,
            'providerName' => 'telegram',
        ]);

        if (!$identity) {
            return;
        }

        $chatMessage = new ChatMessage(
            $message,
            (new TelegramOptions())
                ->chatId($identity->getProviderId())
                ->parseMode(TelegramOptions::PARSE_MODE_HTML),
        );

        try {
            $this->chatter->send($chatMessage);
        } catch (\Exception) {
        }
    }
}
