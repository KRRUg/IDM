<?php

namespace App\DataFixtures;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\ApiUser;
use App\Entity\UserClan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use NumberToWords\NumberToWords;
use Symfony\Component\Security\Core\Encoder\SodiumPasswordEncoder;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $encoder = new SodiumPasswordEncoder();

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('de');

        // create 20 Users
        for ($i = 0; $i < 20; ++$i) {
            $user = new User();
            $user->setNickname('User '.$i);
            $user->setEmail('user'.$i.'@localhost.local');
            $user->setFirstname("User");
            $user->setSurname(ucfirst($numberTransformer->toWords($i)));
            $user->setUuid(Uuid::fromInteger(strval($i)));
            $user->setStatus(1);
            $user->setPassword($encoder->encodePassword('user'.$i, null));
            $user->setEmailConfirmed(1 != $i % 5);
            $user->setInfoMails(1 == ($i + 1) % 5);
            $user->setPersonalDataLocked(1 == ($i + 1) % 7);
            $user->setPersonalDataConfirmed(1 != ($i + 2) % 3);
            $user->setIsSuperadmin(false);
            $user->setGender(1 == $i % 3 ? 'f' : 'm');

            $manager->persist($user);
        }

        $manager->flush();

        $ghost = new User();
        $ghost->setUuid(Uuid::fromInteger(42));
        $ghost->setNickname("DeletedUser");
        $ghost->setFirstname("Deleted");
        $ghost->setSurname("User");
        $ghost->setStatus(-1);
        $ghost->setEmail('ghost@localhost.local');
        $ghost->setPassword($encoder->encodePassword('ghost', null));
        $ghost->setEmailConfirmed(1);
        $ghost->setInfoMails(true);
        $ghost->setPersonalDataLocked(false);
        $ghost->setPersonalDataConfirmed(false);
        $manager->persist($ghost);
        $manager->flush();

        // assign Clans to Users

        $clan = new Clan();
        $clan->setName('Clan 1');
        $clan->setClantag('CL1');
        $clan->setUuid(Uuid::fromInteger(strval(1001)));
        $clan->setDescription('wubwub');
        $clan->setWebsite('http://localhost');
        $clan->setJoinPassword(password_hash('clan1', PASSWORD_ARGON2ID));

        $clanuser1 = $manager->getRepository(User::class)->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000001']);

        $userclan = new UserClan();
        $userclan->setAdmin(true);
        $userclan->setUser($clanuser1);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $clanuser2 = $manager->getRepository(User::class)->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000002']);

        $userclan = new UserClan();
        $userclan->setUser($clanuser2);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $manager->persist($clan);

        $userclan = new UserClan();
        $userclan->setAdmin(false);
        $userclan->setUser($ghost);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $clan = new Clan();
        $clan->setName('Clan 2');
        $clan->setClantag('CL2');
        $clan->setUuid(Uuid::fromInteger(strval(1002)));
        $clan->setDescription('wubwub');
        $clan->setWebsite('http://localhost2');
        $clan->setJoinPassword(password_hash('clan2', PASSWORD_ARGON2ID));

        $clanuser3 = $manager->getRepository(User::class)->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000003']);
        $userclan = new UserClan();
        $userclan->setAdmin(true);
        $userclan->setUser($clanuser3);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $userclan = new UserClan();
        $userclan->setUser($clanuser2);
        $userclan->setClan($clan);

        $manager->persist($userclan);

        $clanuser4 = $manager->getRepository(User::class)->findOneBy(['uuid' => '00000000-0000-0000-0000-000000000004']);
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
        $admin->setFirstname('Ali');
        $admin->setSurname('Admin');
        $admin->setEmail('admin@localhost.local');
        $admin->setStatus(1);
        $admin->setPassword($encoder->encodePassword('admin', null));
        $admin->setEmailConfirmed(true);
        $admin->setInfoMails(false);
        $admin->setPersonalDataLocked(false);
        $admin->setPersonalDataConfirmed(false);
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
