<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

class Bulk
{
    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     * @var array
     */
    public array $uuid = [];
}