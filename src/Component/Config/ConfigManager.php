<?php

namespace App\Component\Config;

use Adbar\Dot;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ConfigManager
{
    private const CONFIG_PARAMS_TO_OUTPUT = [
        'base_url',
        'sitename',
        'jwt.access_token_ttl',
        'contacts.available',
        'contacts.formats',
        'contacts.regex',
        'blog_posts.restore_days_limit',
        'users.contacts',
        'users.enable_website',
        'users.enable_phone',
        'users.enable_city',
        'users.enable_birthdate',
        'users.enable_contacts',
        'users.restore_days_limit'
    ];

    public function __construct(
        private ContainerBagInterface $config
    )
    {}

    public function parameters(): array
    {
        $parameters = new Dot();

        foreach (self::CONFIG_PARAMS_TO_OUTPUT as $item) {
            $parameters->set($item, $this->config->get('app.' . $item));
        }

        return $parameters->all();
    }
}