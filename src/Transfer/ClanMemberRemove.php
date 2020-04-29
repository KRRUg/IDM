<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class ClanMemberRemove
{
    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     */
    public $users = [];

    /**
     * @Assert\Type(type="boolean")
     */
    public $strict;
}
