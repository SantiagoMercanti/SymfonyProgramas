<?php

namespace App\Service;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorFormatter
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $v) {
            $field = $v->getPropertyPath() ?: 'general';
            $errors[$field][] = $v->getMessage();
        }

        return ['errors' => $errors];
    }
}
