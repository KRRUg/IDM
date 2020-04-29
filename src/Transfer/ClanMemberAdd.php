<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class ClanMemberAdd
{
    /**
     * @Assert\Type(type="string")
     */
    public $joinPassword;

    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     */
    public $users = [];
}
