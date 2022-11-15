<?php

namespace App\Service;

use App\Entity\Clan;
use App\Repository\ClanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class ClanService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ClanRepository $clanRepository, private readonly PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    public function checkCredentials(string $name, string $password)
    {
        if (empty($name) || empty($password)) {
            return false;
        }

        $clan = $this->clanRepository->findOneBy(['name' => $name]);

        if (empty($clan)) {
            return false;
        }

        $hasher = $this->hasherFactory->getPasswordHasher(Clan::class);

        $valid = $hasher->verify($clan->getJoinPassword(), $password);
        if ($hasher->needsRehash($clan->getJoinPassword())) {
            // Rehash legacy Password if needed
            $clan->setJoinPassword($hasher->hash($password));
            $this->em->flush();
        }

        if ($valid) {
            return $clan;
        } else {
            // User or Password false
            return false;
        }
    }
}
