<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class AuthObject
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public string $name;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public string $secret;
}