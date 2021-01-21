<?php

namespace App\Transfer;

class ValidationError extends Error
{
    public string $field;

    public string $constraint;

    static public function withProperty(string $field, string $constraint)
    {
        $ret = new self();
        $ret->message = "Validation error";
        $ret->detail = "Field {$field} was violated at constraint {$constraint}";
        $ret->field = $field;
        $ret->constraint = $constraint;
        return $ret;
    }
}