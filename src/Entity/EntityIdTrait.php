<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

trait EntityIdTrait
{
    /**
     * The unique auto incremented primary key.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    #[Groups(['read'])]
    protected $id;

    /**
     * The internal primary identity key.
     *
     * @OA\Property(type="string")
     */
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Assert\Uuid(strict: false)]
    #[Groups(['read'])]
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

    #[ORM\PrePersist]
    public function generateUuid()
    {
        if ($this->getUuid() === null) {
            $this->setUuid(Uuid::uuid4());
        }
    }
}
