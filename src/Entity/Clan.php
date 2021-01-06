<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ClanRepository")
 * @ORM\Table(name="clan")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"name"}, message="There is already a clan with this name")
 * @UniqueEntity(fields={"clantag"}, message="There is already a tag with this name")
 */
class Clan
{
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    use EntityIdTrait;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 6,
     *      max = 256,
     *      minMessage = "The password must be at least {{ limit }} characters long",
     *      maxMessage = "The password cannot be longer than {{ limit }} characters",
     *      groups = {"Transfer"}
     * )
     * @Groups({"read", "write"})
     */
    private $joinPassword;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read"})
     */
    private $modifiedAt;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\UserClan",
     *     mappedBy="clan",
     *     cascade={"all"},
     * )
     * @Groups({"read"})
     */
    private $users;

    /**
     * @ORM\Column(type="string", length=24, unique=true)
     * @Assert\NotBlank(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $clantag;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=4096, nullable=true)
     * @Groups({"read", "write"})
     */
    private $description;


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

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedDatetime() {
        // update the modified time and creation time
        $this->setModifiedAt(new \DateTime());
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}
