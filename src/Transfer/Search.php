<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class Search
{
    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     *
     * @var array|null
     */
    public $uuid = [];

    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank(allowNull=true)
     *
     * @var string
     */
    public $nickname;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool|null
     */
    public $superadmin;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool|null
     */
    public $newsletter;
}
