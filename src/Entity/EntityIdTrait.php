<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

trait EntityIdTrait
{
    /**
     * The unique auto incremented primary key.
     *
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @Groups({"default", "clanview"})
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * The internal primary identity key.
     *
     * @var UuidInterface
     *
     * @SWG\Property(type="string")
     * @ORM\Column(type="uuid", unique=true)
     * @Groups({"default", "clanview"})
     */
    protected $uuid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }
}