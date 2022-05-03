<?php

namespace App\Component\Validator;

use App\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints as BaseConstraints;

class ConstraintBuilder
{
    public function numeric(string $message = 'common.constraints.numeric'): Constraints\Numeric
    {
        return new Constraints\Numeric(message: $message);
    }

    public function notNumeric(string $message = 'common.constraints.not_numeric'): Constraints\Numeric
    {
        return new Constraints\Numeric(false, message: $message);
    }

    public function blank(string $message = 'common.constraints.blank'): BaseConstraints\Blank
    {
        return new BaseConstraints\Blank(message: $message);
    }

    public function notBlank(string $message = 'common.constraints.not_blank'): BaseConstraints\NotBlank
    {
        return new BaseConstraints\NotBlank(message: $message);
    }

    public function type(array|string $type, string $message = 'common.constraints.type'): BaseConstraints\Type
    {
        return new BaseConstraints\Type(type: $type, message: $message);
    }

    public function email(string $message = 'common.constraints.email'): BaseConstraints\Email
    {
        return new BaseConstraints\Email(message: $message);
    }

    public function minLength(int $min, string $message = 'common.constraints.min_length'): BaseConstraints\Length
    {
        return new BaseConstraints\Length(min: $min, minMessage: $message, options: ['allowEmptyString' => true]);
    }

    public function maxLength(int $max, string $message = 'common.constraints.max_length'): BaseConstraints\Length
    {
        return new BaseConstraints\Length(max: $max, minMessage: $message, options: ['allowEmptyString' => true]);
    }

    public function url(string $message = 'common.constraints.url'): BaseConstraints\Url
    {
        return new BaseConstraints\Url(relativeProtocol: true, message: $message);
    }

    public function positive(string $message = 'common.constraints.positive'): BaseConstraints\Positive
    {
        return new BaseConstraints\Positive(message: $message);
    }

    public function lessOrEqual(int $max, string $message = 'common.constraints.less_or_equal'): BaseConstraints\LessThanOrEqual
    {
        return new BaseConstraints\LessThanOrEqual($max, message: $message);
    }

    public function identicalToOneOf(array $values, string $message = 'common.constraints.identical_to_one_of'): BaseConstraints\AtLeastOneOf
    {
        $constraints = array_map(function ($value) {
            return new BaseConstraints\IdenticalTo($value);
        }, $values);

        $constraints[] = new BaseConstraints\Blank();

        return new BaseConstraints\AtLeastOneOf(constraints: $constraints, message: $message, includeInternalMessages: false);
    }

    public function allIdenticalToOneOf(array $values, string $message = 'common.constraints.all_identical_to_one_of'): BaseConstraints\All
    {
        $constraints = array_map(function ($value) {
            return new BaseConstraints\IdenticalTo($value);
        }, $values);

        return new BaseConstraints\All(
            new BaseConstraints\AtLeastOneOf(constraints: $constraints, message: $message, includeInternalMessages: false)
        );
    }
}
