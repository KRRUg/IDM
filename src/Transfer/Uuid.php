<?php

namespace App\Transfer;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class Uuid
{
    /**
     * @Assert\NotBlank()
     * @Assert\Uuid(strict=false)
     */
    public UuidInterface $uuid;
}