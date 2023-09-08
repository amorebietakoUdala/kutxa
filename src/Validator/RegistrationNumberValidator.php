<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RegistrationNumberValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        /* @var App\Validator\RegistrationNumber $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (preg_match('/^\d+\/\d+$/', (string) $value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
