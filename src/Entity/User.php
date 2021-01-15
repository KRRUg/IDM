<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="gamer")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"email"}, groups={"Default", "Unique"}, repositoryMethod="findByCi", message="There is already an account with this email")
 * @UniqueEntity(fields={"nickname"}, groups={"Default", "Unique"}, message="There is already an account with this nickname")
 */
class User
{
    public function __construct()
    {
        $this->clans = new ArrayCollection();
    }

    use EntityIdTrait;

    /**
     * @ORM\Column(type="string", length=320, unique=true)
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Assert\Email(groups={"Default", "Transfer", "Create"})
     * @Groups({"read", "write"})
     */
    private $email;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\Length(
     *      min = 6,
     *      max = 128,
     *      minMessage = "The password must be at least {{ limit }} characters long",
     *      maxMessage = "The password cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Transfer", "Create"}
     * )
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Groups({"write"})
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Assert\Length(
     *      min = 1,
     *      max = 64,
     *      minMessage = "The nickname must be at least {{ limit }} characters long",
     *      maxMessage = "The nickname cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Default", "Transfer", "Create"}
     * )
     * @Groups({"read", "write"})
     */
    private $nickname;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"read"})
     * TODO check if status can be IDM internal
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $surname;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     * @Groups({"read", "write"})
     */
    private $postcode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     * @Assert\Country(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     * @Assert\Regex("/^[+]?\d([ \/()]?\d)*$/", message="Invalid phone number format.", groups={"Default", "Transfer"})
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     * @Assert\Choice({"m","w","d"}, groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $gender;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"read", "write"})
     */
    private $emailConfirmed;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"read"})
     */
    private $isSuperadmin = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $steamAccount;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read"})
     */
    private $registeredAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read"})
     */
    private $modifiedAt;

    /**
     * @ORM\Column(type="string", length=4096, nullable=true)
     * @Groups({"read", "write"})
     */
    private $hardware;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"read", "write"})
     */
    private $infoMails;

    /**
     * @ORM\Column(type="string", length=4096, nullable=true)
     * @Groups({"read", "write"})
     */
    private $statements;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\UserClan",
     *     mappedBy="user",
     *     cascade={"all"},
     * )
     * @Groups({"read"})
     */
    private $clans;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private $birthdate;

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
     * @return Collection|UserClan[]|null
     */
    public function getClans(): Collection
    {
        return $this->clans;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedDatetime() {
        // update the modified time and creation time
        $this->setModifiedAt(new \DateTime());
        if ($this->getRegisteredAt() === null) {
            $this->setRegisteredAt(new \DateTime());
        }
    }
}
