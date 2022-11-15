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
     * @var string
     */
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank(allowNull: true)]
    public $nickname;

    /**
     * @var bool|null
     */
    #[Assert\Type(type: 'boolean')]
    public $superadmin;

    /**
     * @var bool|null
     */
    #[Assert\Type(type: 'boolean')]
    public $newsletter;
}
