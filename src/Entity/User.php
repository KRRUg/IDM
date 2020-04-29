<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="app_users")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface
{
    public function __construct()
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::uuid4();
        }
        $this->setRegisteredAt(new \DateTime());
        if (null == $this->getModifiedAt()) {
            $this->setModifiedAt(new \DateTime());
        }
        $this->clans = new ArrayCollection();
    }

    use EntityIdTrait;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"default", "clanview"})
     */
    private $email;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"default", "clanview"})
     */
    private $nickname;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"default", "clanview"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $surname;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("default")
     */
    private $postcode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $gender;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("default")
     */
    private $emailConfirmed;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("default")
     */
    private $isSuperadmin = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $steamAccount;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("default")
     */
    private $registeredAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("default")
     */
    private $modifiedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $hardware;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("default")
     */
    private $infoMails;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("default")
     */
    private $statements;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserClan", mappedBy="user")
     * @Groups("default")
     */
    private $clans;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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

    public function getPostcode(): ?int
    {
        return $this->postcode;
    }

    public function setPostcode(int $postcode): self
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

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     * @Groups({"default", "clanview"})
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return [];
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeInterface $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

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

    public function setInfoMails(bool $infoMails): self
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

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedAtDatetime()
    {
        // update the modified time
        $this->setModifiedAt(new \DateTime());
    }

    /**
     * @return Collection|UserClan[]
     */
    public function getClans(): Collection
    {
        return $this->clans;
    }

}
