<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract\Notification;

use Morfeditorial\MachinimaCoreBundle\Contract\NotificationChannelPort;
use Morfeditorial\MachinimaCoreBundle\Entity\User;

class NullNotificationChannel implements NotificationChannelPort
{
    public function send(User $user, string $message, array $options = []): void
    {
    }
}
