<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="gamer_clan",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="user_clan_unique", columns={"user_id", "clan_id"})
 *     }
 * )
 */
class UserClan
{
    public function __construct()
    {
        if (null == $this->getAdmin()) {
            $this->setAdmin(false);
        }
    }

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="clans")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE", name="user_id")
     */
    private $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Entity\Clan", inversedBy="users")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE", name="clan_id")
     */
    private $clan;

    /**
     * @ORM\Column(type="boolean")
     */
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

    /**
     * @param mixed $user
     */
    public function setUser($user): void
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

    /**
     * @param mixed $clan
     */
    public function setClan($clan): void
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

    /**
     * @param mixed $admin
     */
    public function setAdmin($admin): void
    {
        $this->admin = $admin;
    }
}
