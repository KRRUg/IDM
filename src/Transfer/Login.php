<?php


namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;


final class Login
{
    /**
     * @var string
     * @Assert\Email()
     * @Assert\NotBlank()
     */
    public string $email;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public string $password;
}