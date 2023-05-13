<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\Table(name: 'gamer')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email', repositoryMethod: 'findByCi', groups: ['Default', 'Unique'])]
#[UniqueEntity(fields: ['nickname'], message: 'There is already an account with this nickname', groups: ['Default', 'Unique'])]
class User
{
    use EntityIdTrait;
    use HideableTrait;

    public function __construct()
    {
        $this->clans = new ArrayCollection();
    }

    #[ORM\Column(type: 'string', length: 320, unique: true)]
    #[Assert\NotBlank(groups: ['Default', 'Create'])]
    #[Assert\Email(groups: ['Default', 'Transfer', 'Create'])]
    #[Groups(['read', 'write'])]
    private ?string $email = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['read', 'write'])]
    private bool $emailConfirmed = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['read', 'write'])]
    private ?bool $infoMails = false;

    /**
     * @var ?string The hashed password
     */
    #[ORM\Column(type: 'string')]
    #[Assert\Length(min: 6, max: 128, minMessage: 'The password must be at least {{ limit }} characters long', maxMessage: 'The password cannot be longer than {{ limit }} characters', groups: ['Transfer', 'Create'])]
    #[Assert\NotBlank(groups: ['Default', 'Create'])]
    #[Groups(['write'])]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    #[Assert\NotBlank(groups: ['Default', 'Create'])]
    #[Assert\Length(min: 1, max: 64, minMessage: 'The nickname must be at least {{ limit }} characters long', maxMessage: 'The nickname cannot be longer than {{ limit }} characters', groups: ['Default', 'Transfer', 'Create'])]
    #[Groups(['read', 'write'])]
    private ?string $nickname = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $firstname = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $surname = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['read', 'write'])]
    private ?DateTimeInterface $birthdate = null;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    #[Assert\Choice(['m', 'f', 'x'], groups: ['Default', 'Transfer'])]
    #[Groups(['read', 'write'])]
    private ?string $gender = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['read', 'write'])]
    private ?bool $personalDataConfirmed = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['read', 'write'])]
    private ?bool $personalDataLocked = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['read'])]
    private ?bool $isSuperadmin = false;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $postcode = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $street = null;

    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    #[Assert\Country(groups: ['Default', 'Transfer'])]
    #[Groups(['read', 'write'])]
    private ?string $country = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    #[Assert\Regex('/^[+]?\d([ \/()]?\d)*$/', message: 'Invalid phone number format.', groups: ['Default', 'Transfer'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url(groups: ['Default', 'Transfer'])]
    #[Groups(['read', 'write'])]
    private ?string $website = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $steamAccount = null;

    #[ORM\Column(type: 'string', length: 4096, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $hardware = null;

    #[ORM\Column(type: 'string', length: 4096, nullable: true)]
    #[Groups(['read', 'write'])]
    private ?string $statements = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['read'])]
    private ?DateTimeInterface $registeredAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['read'])]
    private ?DateTimeInterface $modifiedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['read'])]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: 'App\Entity\UserClan', cascade: ['all'])]
    #[Groups(['read'])]
    private Collection $clans;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function isPersonalDataConfirmed(): ?bool
    {
        return $this->personalDataConfirmed;
    }

    public function setPersonalDataConfirmed(?bool $personalDataConfirmed): self
    {
        $this->personalDataConfirmed = $personalDataConfirmed;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getEmailConfirmed(): ?bool
    {
        return $this->emailConfirmed;
    }

    public function setEmailConfirmed(bool $emailConfirmed): self
    {
        $this->emailConfirmed = $emailConfirmed;

        return $this;
    }

    public function personalDataLocked(): ?bool
    {
        return $this->personalDataLocked;
    }

    public function setPersonalDataLocked(?bool $personalDataLocked): self
    {
        $this->personalDataLocked = $personalDataLocked;

        return $this;
    }

    public function getIsSuperadmin(): ?bool
    {
        return $this->isSuperadmin;
    }

    public function setIsSuperadmin(?bool $isSuperadmin): self
    {
        $this->isSuperadmin = $isSuperadmin;

        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    public function getSteamAccount(): ?string
    {
        return $this->steamAccount;
    }

    public function setSteamAccount(?string $steamAccount): self
    {
        $this->steamAccount = $steamAccount;

        return $this;
    }

    public function getRegisteredAt(): ?DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(DateTimeInterface $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

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

    public function getHardware(): ?string
    {
        return $this->hardware;
    }

    public function setHardware(?string $hardware): self
    {
        $this->hardware = $hardware;

        return $this;
    }

    public function getInfoMails(): ?bool
    {
        return $this->infoMails;
    }

    public function setInfoMails(?bool $infoMails): self
    {
        $this->infoMails = $infoMails;

        return $this;
    }

    public function getStatements(): ?string
    {
        return $this->statements;
    }

    public function setStatements(?string $statements): self
    {
        $this->statements = $statements;

        return $this;
    }

    public function getClans(): Collection
    {
        return $this->clans;
    }

    public function getBirthdate(): ?DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }
    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModifiedDatetime(): void
    {
        // update the modified time and creation time
        $this->setModifiedAt(new DateTime());
        if ($this->getRegisteredAt() === null) {
            $this->setRegisteredAt(new DateTime());
        }
    }
}
