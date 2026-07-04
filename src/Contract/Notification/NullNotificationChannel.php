<?php

declare(strict_types=1);

namespace App\Contract\Notification;

use App\Contract\NotificationChannelPort;
use App\Entity\User;

class NullNotificationChannel implements NotificationChannelPort
{
    public function send(User $user, string $message, array $options = []): void
    {
    }
}
