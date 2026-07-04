<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\Notification;

use App\Contract\UserNotificationAddressResolver;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TelegramNotificationAddressResolver implements UserNotificationAddressResolver
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function resolveAddress(User $user, string $channel): ?string
    {
        if ('telegram' !== $channel) {
            return null;
        }

        $identity = $this->em->getRepository(\App\Entity\UserIdentity::class)
            ->findOneBy([
                'user' => $user,
                'providerName' => 'telegram',
            ]);

        return $identity?->getProviderId();
    }
}
