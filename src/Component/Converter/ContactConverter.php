<?php

namespace App\Component\Converter;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ContactConverter
{
    private array $formats =    [];
    private array $regex =      [];

    public function __construct(
        private ContainerBagInterface $parameters
    )
    {
        $this->formats =    $this->parameters->get('app.contacts.formats');
        $this->regex =      $this->parameters->get('app.contacts.regex');
    }

    public function convertArray(array $contacts): array
    {
        return array_map(function (array $contact): array {
            $contact['value'] = $this->convert($contact['contact'], $contact['value']);

            return $contact;
        }, $contacts);
    }

    public function convert(string $contact, string $value): string
    {
        return !isset($this->regex[$contact]) ? $value : preg_replace('/' . $this->regex[$contact] . '/i', '$1', $value);
    }

    public function addLinks(array $contacts): array
    {
        return array_map(function (array $contact): array {
            $contact['link'] = $this->toLink($contact['contact'], $contact['value']);
            return $contact;
        }, $contacts);
    }


    public function toLink(string $contact, string $value): string
    {
        if (!$value) {
            return '';
        }

        return isset($this->formats[$contact]) ? sprintf($this->formats[$contact], $value) : '';
    }
}
