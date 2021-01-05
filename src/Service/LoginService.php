<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LoginService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
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

        $valid = $this->passwordEncoder->isPasswordValid($user, $password);
        if ($this->passwordEncoder->needsRehash($user)) {
            //Rehash legacy Password if needed
            $user->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user,
                    $password
                )
            );
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
