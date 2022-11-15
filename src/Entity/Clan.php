<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\ClanRepository')]
#[ORM\Table(name: 'clan')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'There is already a clan with this name', groups: ['Default', 'Unique'])]
#[UniqueEntity(fields: ['clantag'], message: 'There is already a tag with this name', groups: ['Default', 'Unique'])]
class Clan
{
    use EntityIdTrait;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    #[Assert\NotBlank(groups: ['Default', 'Create'])]
    #[Assert\Length(min: 1, max: 64, minMessage: 'The name must be at least {{ limit }} characters long', maxMessage: 'The name cannot be longer than {{ limit }} characters', groups: ['Default', 'Transfer', 'Create'])]
    #[Groups(['read', 'write'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Length(min: 6, max: 128, minMessage: 'The password must be at least {{ limit }} characters long', maxMessage: 'The password cannot be longer than {{ limit }} characters', groups: ['Transfer', 'Create'])]
    #[Assert\NotBlank(groups: ['Default', 'Create'])]
    #[Groups(['write'])]
    private ?string $joinPassword = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['read'])]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['read'])]
    private ?DateTimeInterface $modifiedAt = null;

    #[ORM\OneToMany(mappedBy: 'clan', targetEntity: 'App\Entity\UserClan', cascade: ['all'])]
    #[Groups(['read'])]
    private Collection $users;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    #[Assert\Length(min: 1, max: 10, minMessage: 'The clantag must be at least {{ limit }} characters long', maxMessage: 'The clantag cannot be longer than {{ limit }} characters', groups: ['Default', 'Transfer', 'Create'])]
    #[Assert\NotBlank(groups: ['Default', 'Create'])]
    #[Groups(['read', 'write'])]
    private ?string $clantag = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url(groups: ['Default', 'Transfer'])]
    #[Groups(['read', 'write'])]
    private ?string $website = null;

    #[ORM\Column(type: 'string', length: 4096, nullable: true)]
    #[Assert\Length(max: 4096, maxMessage: 'The clan description cannot be longer than {{ limit }} characters', groups: ['Default', 'Transfer'])]
    #[Groups(['read', 'write'])]
    private ?string $description = null;

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

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(DateTimeInterface $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModifiedDatetime()
    {
        // update the modified time and creation time
        $this->setModifiedAt(new DateTime());
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new DateTime());
        }
    }
}
