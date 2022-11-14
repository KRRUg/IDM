<?php

namespace App\Transfer;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UuidObject
{
    /**
     * @Assert\Uuid(strict=false)
     * @Assert\NotBlank()
     */
    public UuidInterface $uuid;
}
