<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Contract\NotificationChannelPort;
use App\Contract\UserNotificationAddressResolver;
use App\Entity\User;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class TelegramNotificationService implements NotificationChannelPort
{
    public function __construct(
        private UserNotificationAddressResolver $addressResolver,
        private ChatterInterface $chatter,
    ) {
    }

    public function send(User $user, string $message, array $options = []): void
    {
        $chatId = $this->addressResolver->resolveAddress($user, 'telegram');

        if (null === $chatId) {
            return;
        }

        $chatMessage = new ChatMessage(
            $message,
            (new TelegramOptions())
                ->chatId($chatId)
                ->parseMode(TelegramOptions::PARSE_MODE_HTML),
        );

        $this->chatter->send($chatMessage);
    }
}
