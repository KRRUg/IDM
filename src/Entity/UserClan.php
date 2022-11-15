<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'gamer_clan')]
#[ORM\UniqueConstraint(name: 'user_clan_unique', columns: ['user_id', 'clan_id'])]
#[ORM\Entity]
class UserClan
{
    public function __construct()
    {
        if (null == $this->getAdmin()) {
            $this->setAdmin(false);
        }
    }

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'clans')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', name: 'user_id')]
    private $user;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Clan', inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', name: 'clan_id')]
    private $clan;

    #[ORM\Column(type: 'boolean')]
    private $admin;

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
