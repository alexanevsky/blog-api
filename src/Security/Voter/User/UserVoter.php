<?php

namespace App\Security\Voter\User;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const ATTR_VIEW =    'view';
    public const ATTR_UPDATE =  'update';
    public const ATTR_TRASH =   'trash';
    public const ATTR_UNTRASH = 'untrash';
    public const ATTR_ERASE =   'erase';

    public const ATTRIBUTES = [
        self::ATTR_VIEW,
        self::ATTR_UPDATE,
        self::ATTR_TRASH,
        self::ATTR_UNTRASH,
        self::ATTR_ERASE
    ];

    /**
     * List of votes in which a user from the authentication token is not required.
     */
    protected const TOKEN_USER_IS_OPTIONAL = [
        self::ATTR_VIEW
    ];

    protected function supports(string $attribute, $user = null): bool
    {
        return $user instanceof User;
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser() ?: null;

        if (!$user instanceof User) {
            return false;
        } elseif (!in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        } elseif (!in_array($attribute, self::TOKEN_USER_IS_OPTIONAL, true) && !$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_VIEW:
                return !$subject->isErased() && (!$subject->isTrashed() || $user?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]));
            case self::ATTR_UPDATE:
                return !$subject->isErased() && !$subject->isTrashed() && ($user === $subject || $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]));
            case self::ATTR_TRASH:
                return !$subject->isErased() && !$subject->isTrashed() && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]) && $user !== $subject;
            case self::ATTR_UNTRASH:
                return !$subject->isErased() && $subject->isTrashed() && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]);
            case self::ATTR_ERASE:
                return !$subject->isErased() && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]) && $user !== $subject;
        }

        return false;
    }
}
