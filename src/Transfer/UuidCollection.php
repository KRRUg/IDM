<?php


namespace App\Transfer;


use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UuidCollection
{
    /**
     * @var UuidInterface[]
     * @Assert\Collection()
     * @Assert\All(@Assert\Uuid(strict=false))
     */
    public $uuid;
}