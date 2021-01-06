<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class LoginService
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private PasswordEncoderInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, PasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function checkCredentials(string $email, string $password)
    {
        if (empty($email) || empty($password)) {
            return false;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (empty($user)) {
            return false;
        }

        $valid = $this->passwordEncoder->isPasswordValid($user->getPassword(), $password, null);
        if ($this->passwordEncoder->needsRehash($user->getPassword())) {
            //Rehash legacy Password if needed
            $user->setPassword($this->passwordEncoder->encodePassword($password, null));
            $this->em->flush();
        }

        if ($valid) {
            return $user;
        } else {
            // User or Password false
            return false;
        }
    }
}
