<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ClanRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Clan
{
    public function __construct()
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::uuid4();
        }
        $this->setCreatedAt(new \DateTime());
        if (null == $this->getModifiedAt()) {
            $this->setModifiedAt(new \DateTime());
        }
        $this->users = new ArrayCollection();
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * The internal primary identity key.
     *
     * @var UuidInterface
     *
     * @ORM\Column(type="uuid", unique=true)
     * @Groups({"default", "clanview"})
     * @Assert\NotBlank
     */
    protected $uuid;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"default", "clanview"})
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $joinPassword;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("clanview")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("clanview")
     */
    private $modifiedAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserClan", mappedBy="clan")
     * @Groups("clanview")
     */
    private $users;

    /**
     * @ORM\Column(type="string", length=24, unique=true)
     * @Groups({"default", "clanview"})
     * @Assert\NotBlank
     */
    private $clantag;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"default", "clanview"})
     * @Assert\Url()
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"default", "clanview"})
     */
    private $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getJoinPassword(): ?string
    {
        return $this->joinPassword;
    }

    public function setJoinPassword(string $joinPassword): self
    {
        $this->joinPassword = $joinPassword;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeInterface $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * @return Collection|UserClan[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedAtDatetime()
    {
        // update the modified time
        $this->setModifiedAt(new \DateTime());
    }

    public function getClantag(): ?string
    {
        return $this->clantag;
    }

    public function setClantag(string $clantag): self
    {
        $this->clantag = $clantag;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

}
