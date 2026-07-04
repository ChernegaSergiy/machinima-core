<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\User;

interface NotificationChannelPort
{
    public function send(User $user, string $message, array $options = []): void;
}
