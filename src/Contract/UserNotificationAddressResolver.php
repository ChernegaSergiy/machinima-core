<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\User;

interface UserNotificationAddressResolver
{
    public function resolveAddress(User $user, string $channel): ?string;
}
