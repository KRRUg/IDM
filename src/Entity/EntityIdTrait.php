<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

trait EntityIdTrait
{
    /**
     * The unique auto incremented primary key.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * The internal primary identity key.
     *
     * @SWG\Property(type="string")
     * @ORM\Column(type="uuid", unique=true)
     * @Groups({"read"})
     */
    protected $uuid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @ORM\PrePersist()
     */
    public function generateUuid() {
        if ($this->getUuid() === null) {
            $this->setUuid(Uuid::uuid4());
        }
    }
}