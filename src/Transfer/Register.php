<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

class Register
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public string $email;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 6,
     *      max = 256,
     *      minMessage = "Your password must be at least {{ limit }} characters long",
     *      maxMessage = "Your password cannot be longer than {{ limit }} characters"
     * )
     */
    public string $password;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public string $nickname;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public string $firstname;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    public string $surname;

    /**
     * @var bool
     * @Assert\NotNull()
     * @Assert\Type("bool")
     */
    public bool $infoMail;
}