<?php

namespace App\DataFixtures;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\ApiUser;
use App\Entity\UserClan;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;


class AppFixtures extends Fixture
{
    /**
     * @var PasswordEncoderInterface
     */
    private PasswordEncoderInterface $encoder;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    public function __construct(PasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        // create 20 Users
        for ($i = 0; $i < 20; ++$i) {
            $user = new User();
            $user->setNickname('User '.$i);
            $user->setEmail('user'.$i.'@localhost.local');
            $user->setUuid(Uuid::fromInteger(strval($i)));
            $user->setStatus(1);
            $user->setPassword($this->encoder->encodePassword('user'.$i, null));
            $user->setEmailConfirmed(1 == mt_rand(0, 1));
            $user->setInfoMails(1 == mt_rand(0, 1));
            $user->setIsSuperadmin(false);

            $manager->persist($user);
        }

        $manager->flush();

        // assign Clans to Users

        $clan = new Clan();
        $clan->setName('Clan 1');
        $clan->setClantag('CL1');
        $clan->setUuid(Uuid::fromInteger(strval(1001)));
        $clan->setDescription('wubwub');
        $clan->setWebsite('http://localhost');
        $clan->setJoinPassword(password_hash('clan1', PASSWORD_ARGON2ID));

        $clanuser1 = $manager->getRepository('App:User')->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000001']);

        $userclan = new UserClan();
        $userclan->setAdmin(true);
        $userclan->setUser($clanuser1);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $clanuser2 = $manager->getRepository('App:User')->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000002']);

        $userclan = new UserClan();
        $userclan->setUser($clanuser2);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $manager->persist($clan);

        $clan = new Clan();
        $clan->setName('Clan 2');
        $clan->setClantag('CL2');
        $clan->setUuid(Uuid::fromInteger(strval(1002)));
        $clan->setDescription('wubwub');
        $clan->setWebsite('http://localhost2');
        $clan->setJoinPassword(password_hash('clan2', PASSWORD_ARGON2ID));

        $clanuser3 = $manager->getRepository('App:User')->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000003']);
        $userclan = new UserClan();
        $userclan->setAdmin(true);
        $userclan->setUser($clanuser3);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $userclan = new UserClan();
        $userclan->setUser($clanuser2);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $clanuser4 = $manager->getRepository('App:User')->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000004']);
        $userclan = new UserClan();
        $userclan->setUser($clanuser4);
        $userclan->setClan($clan);

        $manager->persist($userclan);
        $manager->persist($clan);

        // create an empty clan
        $clan = new Clan();
        $clan->setName('Clan 3');
        $clan->setClantag('CL3');
        $clan->setUuid(Uuid::fromInteger(strval(1003)));
        $clan->setJoinPassword(password_hash('clan3', PASSWORD_ARGON2ID));
        $manager->persist($clan);

        // create admin user
        $admin = new User();
        $admin->setNickname('Admin');
        $admin->setEmail('admin@localhost.local');
        $admin->setStatus(1);
        $admin->setPassword($this->encoder->encodePassword('admin', null));
        $admin->setEmailConfirmed(1);
        $admin->setInfoMails(false);
        $admin->setIsSuperadmin(true);

        $manager->persist($admin);


        // create one API-Key
        $apiuser = new ApiUser();
        $apiuser->setName('Example');
        $apiuser->setApiToken('1234');

        $manager->persist($apiuser);

        $manager->flush();
    }
}
