<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
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

    public function listUser($searchParameter = null, $searchValue = null)
    {
        if ('uuid' === $searchParameter) {
            return $this->userRepository->findBy(['uuid' => $searchValue]);
        } elseif ('externId' === $searchParameter) {
            return $this->userRepository->findBy(['externId' => $searchValue]);
        } elseif ('email' === $searchParameter) {
            return $this->userRepository->findBy(['email' => $searchValue]);
        } else {
            return $this->userRepository->findAll();
        }
    }

    public function editUser($userdata)
    {
        $user = $this->userRepository->findOneBy(['uuid' => $userdata['uuid']]);

        if (null !== $userdata['email']) {
            $user->setEmail($userdata['email']);
            $user->setEmailConfirmed(false);
            //TODO: resend Confirmation Mail
        }
        if (null !== $userdata['confirmed']) {
            if ('true' === $userdata['confirmed'] || true === $userdata['confirmed']) {
                $user->setEmailConfirmed(true);
            } elseif ('false' === $userdata['confirmed'] || false === $userdata['confirmed']) {
                $user->setEmailConfirmed(false);
            }
        }
        if (null !== $userdata['superadmin']) {
            if ('true' === $userdata['superadmin']) {
                $user->setIsSuperadmin(true);
            } elseif ('false' === $userdata['superadmin']) {
                $user->setIsSuperadmin(false);
            }
        }

        if (null !== $userdata['status']) {
            $user->setStatus(intval($userdata['status']));
        }
        if (null !== $userdata['postcode']) {
            $user->setPostcode(intval($userdata['postcode']));
        }

        if (null !== $userdata['nickname']) {
            $user->setNickname($userdata['nickname']);
        }
        if (null !== $userdata['firstname']) {
            $user->setFirstname($userdata['firstname']);
        }
        if (null !== $userdata['surname']) {
            $user->setSurname($userdata['surname']);
        }
        if (null !== $userdata['city']) {
            $user->setCity($userdata['city']);
        }
        if (null !== $userdata['country']) {
            $user->setCountry($userdata['country']);
        }
        if (null !== $userdata['phone']) {
            $user->setPhone($userdata['phone']);
        }
        if (null !== $userdata['gender']) {
            $user->setGender($userdata['gender']);
        }
        if (null !== $userdata['infoMails']) {
            if ('true' === $userdata['infoMails'] || true === $userdata['infoMails']) {
                $user->setInfoMails(true);
            } elseif ('false' === $userdata['infoMails'] || false === $userdata['infoMails']) {
                $user->setInfoMails(false);
            }
        }
        if (null !== $userdata['website']) {
            $user->setWebsite($userdata['website']);
        }
        if (null !== $userdata['steamAccount']) {
            $user->setSteamAccount($userdata['steamAccount']);
        }
        if (null !== $userdata['hardware']) {
            $user->setHardware($userdata['hardware']);
        }
        if (null !== $userdata['favoriteGuns']) {
            $user->setFavoriteGuns($userdata['favoriteGuns']);
        }
        if (null !== $userdata['statements']) {
            $user->setStatements($userdata['statements']);
        }

        try {
            $this->em->flush();

            return $user;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function enableUser(string $uuid)
    {
        try {
            // fetches the UserEntity from the Repository and enables it
            $user = $this->userRepository->findOneBy(['uuid' => $uuid]);
            $user->setStatus(1);
            $this->em->flush();

            return $user;
        } catch (\Exception $exception) {
            // TODO: return actual Exception
            return null;
        }
    }

    public function disableUser(string $uuid)
    {
        try {
            // fetches the UserEntity from the Repository and disables it
            $user = $this->userRepository->findOneBy(['uuid' => $uuid]);
            $user->setStatus(-1);
            $this->em->flush();

            return $user;
        } catch (\Exception $exception) {
            // TODO: return actual Exception
            return null;
        }
    }

    public function createUser(array $userdata)
    {
        $email = $userdata['email'];
        $password = $userdata['password'];
        $nickname = $userdata['nickname'];
        $confirmed = $userdata['confirmed'];
        $infoMails = $userdata['infoMails'];

        $user = new User();
        $user->setEmail($email);
        $user->setNickname($nickname);
        $user->setStatus(1);
        $user->setEmailConfirmed($confirmed);
        $user->setPassword(
            $this->passwordEncoder->encodePassword($user, $password)
        );

        if ($infoMails) {
            if ('true' === $infoMails || true === $infoMails) {
                $user->setInfoMails(true);
            } elseif ('false' === $infoMails || false === $infoMails) {
                $user->setInfoMails(false);
            }
        } else {
            $user->setInfoMails(true);
        }

        try {
            $this->em->persist($user);
            $this->em->flush();

            return $user;
        } catch (\Exception $exception) {
            // TODO: return actual Exception
            return false;
        }
    }

    public function deleteUser(string $uuid)
    {
        $user = $this->userRepository->findOneBy(['uuid' => $uuid]);

        try {
            $this->em->remove($user);
            $this->em->flush();

            return true;
        } catch (\Exception $exception) {
            // TODO: return actual Exception
            return false;
        }
    }

    public function getUser(string $uuid)
    {
        return $this->userRepository->findOneBy(['uuid' => $uuid]);
    }
}
