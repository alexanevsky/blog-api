<?php

namespace App\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NumericValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Numeric) {
            throw new UnexpectedTypeException($constraint, Numeric::class);
        }

        if (empty($value)) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'value');
        }

        $value = (string) $value;

        if ('' === $value) {
            return;
        }

        if (($constraint->isNumeric && !is_numeric($value)) || (!$constraint->isNumeric && is_numeric($value))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setCode(Numeric::NUMERIC_VALIDATION_ERROR)
                ->addViolation();
        }
    }
}
