<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class AuthObject
{
    /**
     * @Assert\NotBlank()
     */
    public string $name;

    /**
     * @Assert\NotBlank()
     */
    public string $secret;
}
