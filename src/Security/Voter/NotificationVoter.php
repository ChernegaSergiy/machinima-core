<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Security\Voter;

use Morfeditorial\MachinimaCoreBundle\Entity\Notification;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NotificationVoter extends Voter
{
    public const VIEW = 'NOTIFICATION_VIEW';
    public const EDIT = 'NOTIFICATION_EDIT';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Notification;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Notification $subject */
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isOwner = $subject->getUser() && (int) $user->getId() === (int) $subject->getUser()->getId();

        return match ($attribute) {
            self::VIEW, self::EDIT => $isOwner,
            default => false,
        };
    }
}
