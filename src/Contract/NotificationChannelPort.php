<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

use Morfeditorial\MachinimaCoreBundle\Entity\User;

interface NotificationChannelPort
{
    public function send(User $user, string $message, array $options = []): void;
}
