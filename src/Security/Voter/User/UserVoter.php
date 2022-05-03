<?php

namespace App\Security\Voter\User;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const ATTR_VIEW =    'view';
    public const ATTR_EDIT =    'edit';
    public const ATTR_DELETE =  'delete';
    public const ATTR_ERASE =   'erase';
    public const ATTR_RESTORE = 'restore';

    public const ATTRIBUTES = [
        self::ATTR_VIEW,
        self::ATTR_EDIT,
        self::ATTR_DELETE,
        self::ATTR_ERASE,
        self::ATTR_RESTORE
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
                return !$subject->isErased() && (!$subject->isDeleted() || $user?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]));
            case self::ATTR_EDIT:
                return !$subject->isErased() && !$subject->isDeleted() && ($user === $subject || $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]));
            case self::ATTR_DELETE:
                return !$subject->isErased() && !$subject->isDeleted() && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]) && $user !== $subject;
            case self::ATTR_ERASE:
                return !$subject->isErased() && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]) && $user !== $subject;
            case self::ATTR_RESTORE:
                return !$subject->isErased() && $subject->isDeleted() && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]);
        }

        return false;
    }
}
