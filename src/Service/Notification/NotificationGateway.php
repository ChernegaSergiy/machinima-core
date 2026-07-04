<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Contract\NotificationChannelPort;
use App\Entity\User;
use Psr\Log\LoggerInterface;

class NotificationGateway implements NotificationChannelPort
{
    public function __construct(
        private NotificationChannelPort $channel,
        private LoggerInterface $logger,
    ) {
    }

    public function send(User $user, string $message, array $options = []): void
    {
        try {
            $this->channel->send($user, $message, $options);
        } catch (\Exception $e) {
            $this->logger->error('Notification delivery failed', [
                'user' => $user->getId(),
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
