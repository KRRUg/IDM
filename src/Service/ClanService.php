<?php

namespace App\Service;

use App\Entity\Clan;
use App\Repository\ClanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class ClanService
{
    private EntityManagerInterface $em;
    private ClanRepository $clanRepository;
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(EntityManagerInterface $entityManager, ClanRepository $clanRepository, PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->clanRepository = $clanRepository;
        $this->hasherFactory = $hasherFactory;
        $this->em = $entityManager;
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
