<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Notification;
use Symfony\Contracts\EventDispatcher\Event;

final class NotificationReadEvent extends Event
{
    public function __construct(
        private readonly Notification $notification,
    ) {
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
