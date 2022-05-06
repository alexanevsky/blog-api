<?php

namespace App\Resolver\User;

use Alexanevsky\DataResolver\EntityResolver;
use App\Component\Converter\ContactConverter;
use App\Component\Validator\ConstraintBuilder;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraint;

class UserResolverBuilder
{
    private const OPTIONS_TYPES = [
        'username' =>                       'string',
        'alias' =>                          'string',
        'password' =>                       'string',
        'email' =>                          'string',
        'is_email_hidden' =>                'boolean',
        'phone' =>                          'string',
        'website' =>                        'string',
        'contacts' =>                       'array',
        'birthdate' =>                      'string',
        'title' =>                          'string',
        'city' =>                           'string',
        'biography' =>                      'array',
        'is_banned' =>                      'boolean',
        'is_communication_banned' =>        'boolean',
        'is_allowed_adv_notifications' =>   'boolean',
        'roles' =>                          'string[]'
    ];

    private const NULLABLE_OPTIONS = [
        'alias',
        'email',
        'birthdate'
    ];

    private const SETTERS = [
        'password' => 'setPasswordHashed'
    ];

    public function __construct(
        private ConstraintBuilder       $constraints,
        private ContactConverter        $contactConverter,
        private ContainerBagInterface   $parameters,
        private Security                $security,
        private SluggerInterface        $slugger,
        private UserRepository          $usersRepository
    )
    {}

    public function build(User $user): EntityResolver
    {
        $resolver = new EntityResolver();

        foreach ($this->getOptionsTypes($user) as $name => $type) {
            $resolver->define($name, $type, in_array($name, self::NULLABLE_OPTIONS));

            if (isset(self::SETTERS[$name])) {
                $resolver->get($name)->setSetter(self::SETTERS[$name]);
            }

            if ($normalizer = $this->getNormalizer($name, $user)) {
                $resolver->get($name)->setNormalizer($normalizer);
            }

            if ($getterNormalizer = $this->getGetterNormalizer($name, $user)) {
                $resolver->get($name)->setGetterNormalizer($getterNormalizer);
            }

            if ($constraints = $this->getConstraints($name, $user)) {
                $resolver->get($name)->setConstraints($constraints);
            }

            if ($validators = $this->getValidators($name, $user)) {
                foreach ($validators as $validatorError => $validator) {
                    $resolver->get($name)->addValidator($validator, $validatorError);
                }
            }
        }

        $resolver->setEntityNormalizer(function (User $user) {
            if ($user->isBanned()) {
                $user->setRoles([]);
            }
        });

        $resolver->handleEntity($user);

        return $resolver;
    }

    private function getOptionsTypes(User $user): array
    {
        $options = self::OPTIONS_TYPES;

        if (!$user->hasId()) {
            unset($options['is_banned']);
            unset($options['is_communication_banned']);
        }

        if (!$user->hasExtraRoles() && !$user->hasAlias()) {
            unset($options['alias']);
        }

        if (!$user->hasExtraRoles() && !$user->hasTitle()) {
            unset($options['title']);
        }

        if (!$user->hasExtraRoles() && !$user->hasBiography()) {
            unset($options['biography']);
        }

        if (!$this->parameters->get('app.users.enable_website')) {
            unset($options['website']);
        }

        if (!$this->parameters->get('app.users.enable_phone')) {
            unset($options['phone']);
        }

        if (!$this->parameters->get('app.users.enable_city')) {
            unset($options['city']);
        }

        if (!$this->parameters->get('app.users.enable_birthdate')) {
            unset($options['birthdate']);
        }

        if (!$this->parameters->get('app.users.enable_contacts')) {
            unset($options['contacts']);
        }

        if (!$this->security->isGranted(User::ROLE_ADMIN) && !$this->security->isGranted(User::ROLE_USERS_MANAGER)) {
            unset($options['roles']);
            unset($options['is_banned']);
            unset($options['is_communication_banned']);
        }

        return $options;
    }

    private function getNormalizer(string $option, User $user): ?\Closure
    {
        $normalizers = [
            'alias' => function ($alias) {
                return !$alias ? null : strtolower($this->slugger->slug($alias)->toString());
            },
            'email' => function ($email) {
                return !$email ? null : strtolower($email);
            },
            'contacts' => function ($contacts) {
                $contacts = $this->contactConverter->filter($contacts ?? [], $this->parameters->get('app.users.contacts'));
                $contacts = $this->contactConverter->convertArray($contacts);

                return $contacts;
            }
        ];

        return $normalizers[$option] ?? null;
    }

    private function getGetterNormalizer(string $option, User $user): ?\Closure
    {
        $normalizers = [
            'contacts' => function ($contacts) {
                return $this->contactConverter->filter($contacts ?? [], $this->parameters->get('app.users.contacts'));
            },
            'password' => function () {
                return '';
            },
        ];

        return $normalizers[$option] ?? null;
    }

    /**
     * @return Constraint[]
     */
    private function getConstraints(string $option, User $user): array
    {
        $constraints = [
            'alias' => [
                $this->constraints->notNumeric('users.errors.alias.numeric'),
                $this->constraints->minLength(User::ALIAS_MINLENGTH, 'users.errors.alias.short'),
                $this->constraints->maxLength(User::ALIAS_MAXLENGTH, 'users.errors.alias.long')
            ],
            'email' => [
                $this->constraints->notBlank('users.errors.email.empty'),
                $this->constraints->email()
            ],
            'website' => [
                $this->constraints->url()
            ],
            'password' => [
                $this->constraints->minLength(User::PASSWORD_MINLENGTH, 'users.errors.password.short')
            ],
            'roles' => [
                $this->constraints->allIdenticalToOneOf(User::ROLES, 'users.errors.roles.invalid')
            ]
        ];

        return $constraints[$option] ?? [];
    }

    /**
     * @return \Closure[]
     */
    private function getValidators(string $option, User $user): array
    {
        $validators = [
            'alias' => [
                'users.errors.alias.exists' => function ($alias, $default) use ($user) {
                    $found = (!$alias || $alias === $default) ? null : $this->usersRepository->findOneBy(['alias' => $alias]);

                    return (!$found || $found === $user) ? true : false;
                }
            ],
            'email' => [
                'users.errors.email.exists' => function ($email, $default) use ($user) {
                    $found = (!$email || $email === $default) ? null : $this->usersRepository->findOneBy(['email' => $email]);

                    return (!$found || $found === $user) ? true : false;
                }
            ],
            'birthdate' => [
                'users.errors.birthdate.incorrect' => function ($birthdate) {
                    return empty($birthdate) || 1 === preg_match('/^\d{4}-\d{2}-\d{2}$/i', $birthdate) ? true : false;
                }
            ]
        ];

        return $validators[$option] ?? [];
    }
}
