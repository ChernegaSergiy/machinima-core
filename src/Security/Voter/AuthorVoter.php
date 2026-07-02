<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Author;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class AuthorVoter extends Voter
{
    public const VIEW = 'AUTHOR_VIEW';

    public function __construct(
        private Security $security,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Author;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Author $subject */

        if ($subject->getState() !== 'private') {
            return true;
        }

        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ((int)$user->getId() === (int)$subject->getTelegramUserId()) {
            return true;
        }

        return false;
    }
}
