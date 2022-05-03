<?php

namespace App\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Numeric extends Constraint
{
    public const NUMERIC_VALIDATION_ERROR = '11f9ad05-6ecb-43b9-afcf-2a8f6b4c4d88';

    public string $message;
    public bool $isNumeric;
    protected string $messageIsNumeric = 'The string "{{ value }}" can not be numeric.';
    protected string $messageIsNotNumeric = 'The string "{{ value }}" must be numeric.';

    public function __construct(
        bool $isNumeric = true,
        string $message = null,
        array $groups = null,
        $payload = null,
        array $options = []
    )
    {
        $this->isNumeric = $isNumeric;
        $this->message = $message ?? (!$isNumeric ? $this->messageIsNumeric : $this->messageIsNotNumeric);
        $options['isNumeric'] = $isNumeric;

        parent::__construct($options, $groups, $payload);

    }
}
