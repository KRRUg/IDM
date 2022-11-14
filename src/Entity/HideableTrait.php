<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait HideableTrait
{
    /**
     * If status >= 0, the entity is enabled. Otherwise, it will be filtered in all operations.
     *
     * @ORM\Column(type="integer")
     */
    private ?int $status;

    public function isHidden(): bool
    {
        return !empty($this->status) || $this->status < 0;
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
}
