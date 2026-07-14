<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Security\Voter;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const VIEW = 'POST_VIEW';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Content;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Content $subject */
        if ('published' === $subject->getStatus()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        foreach ($subject->getStaff() as $staffItem) {
            $author = $staffItem->getAuthor();
            if ($author && $author->getUser() && (int) $author->getUser()->getId() === (int) $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
