<?php


namespace App\Service;

use App\Repository\ClanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class ClanService
{
    private EntityManagerInterface $em;
    private ClanRepository $clanRepository;
    private PasswordEncoderInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, ClanRepository $clanRepository, PasswordEncoderInterface $passwordEncoder)
    {
        $this->clanRepository = $clanRepository;
        $this->passwordEncoder = $passwordEncoder;
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

        $valid = $this->passwordEncoder->isPasswordValid($clan->getJoinPassword(), $password, null);
        if ($this->passwordEncoder->needsRehash($clan->getJoinPassword())) {
            //Rehash legacy Password if needed
            $clan->setJoinPassword($this->passwordEncoder->encodePassword($password, null));
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