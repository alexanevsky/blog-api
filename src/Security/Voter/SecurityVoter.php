<?php

namespace App\Security\Voter;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SecurityVoter extends Voter
{
    public const ATTR_CREATE_BLOG_CATEGORY =    'create_blog_category';
    public const ATTR_CREATE_BLOG_POST =        'create_blog_post';
    public const ATTR_CREATE_USER =             'create_user';

    public const ATTRIBUTES = [
        self::ATTR_CREATE_BLOG_CATEGORY,
        self::ATTR_CREATE_BLOG_POST,
        self::ATTR_CREATE_USER
    ];

    protected function supports($attribute, $subject = null): bool
    {
        return null === $subject && in_array($attribute, self::ATTRIBUTES, true);
    }

    /**
     * @param null $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        } elseif (!in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_CREATE_BLOG_CATEGORY:
                return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER]);
            case self::ATTR_CREATE_BLOG_POST:
                return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER, User::ROLE_BLOG_AUTHOR]);
            case self::ATTR_CREATE_USER:
                return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_USERS_MANAGER]);
        }

        return false;
    }
}
