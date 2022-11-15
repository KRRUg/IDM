<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gamer_clan')]
#[ORM\UniqueConstraint(name: 'user_clan_unique', columns: ['user_id', 'clan_id'])]
class UserClan
{
    public function __construct()
    {
        if (null == $this->getAdmin()) {
            $this->setAdmin(false);
        }
    }

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'clans')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'clan_id', nullable: false, onDelete: 'CASCADE')]
    private ?Clan $clan = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $admin = false;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(mixed $user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getClan()
    {
        return $this->clan;
    }

    public function setClan(mixed $clan): void
    {
        $this->clan = $clan;
    }

    /**
     * @return mixed
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    public function setAdmin(mixed $admin): void
    {
        $this->admin = $admin;
    }
}
