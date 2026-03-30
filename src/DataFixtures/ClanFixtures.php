<?php

namespace App\DataFixtures;

use App\Entity\Clan;
use App\Entity\User;
use App\Entity\UserClan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class ClanFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $hasherFactory
    )
    {}

    public function load(ObjectManager $manager): void
    {
        // assign Clans to Users
        $hasher = $this->hasherFactory->getPasswordHasher(Clan::class);

        $clan = new Clan();
        $clan->setName('Clan 1');
        $clan->setClantag('CL1');
        $clan->setUuid(Uuid::fromInteger(strval(1001)));
        $clan->setDescription('wubwub');
        $clan->setWebsite('http://localhost');
        $clan->setJoinPassword($hasher->hash('clan1'));
        $manager->persist($clan);

        $user_clan = new UserClan();
        $user_clan->setAdmin(true);
        $user_clan->setUser($this->getReference('user-1', User::class));
        $user_clan->setClan($clan);
        $manager->persist($user_clan);

        $user_clan = new UserClan();
        $user_clan->setUser($this->getReference('user-2', User::class));
        $user_clan->setClan($clan);
        $manager->persist($user_clan);


        $user_clan = new UserClan();
        $user_clan->setAdmin(false);
        $user_clan->setUser($this->getReference('user-ghost', User::class));
        $user_clan->setClan($clan);

        $manager->persist($user_clan);

        $clan = new Clan();
        $clan->setName('Clan 2');
        $clan->setClantag('CL2');
        $clan->setUuid(Uuid::fromInteger(strval(1002)));
        $clan->setDescription('wubwub');
        $clan->setWebsite('http://localhost2');
        $clan->setJoinPassword($hasher->hash('clan2'));

        $user_clan = new UserClan();
        $user_clan->setAdmin(true);
        $user_clan->setUser($this->getReference('user-3', User::class));
        $user_clan->setClan($clan);
        $manager->persist($user_clan);

        $user_clan = new UserClan();
        $user_clan->setUser($this->getReference('user-2', User::class));
        $user_clan->setClan($clan);
        $manager->persist($user_clan);

        $user_clan = new UserClan();
        $user_clan->setUser($this->getReference('user-4', User::class));
        $user_clan->setClan($clan);

        $manager->persist($user_clan);
        $manager->persist($clan);

        // create an empty clan
        $clan = new Clan();
        $clan->setName('Clan 3');
        $clan->setClantag('CL3');
        $clan->setUuid(Uuid::fromInteger(strval(1003)));
        $clan->setJoinPassword($hasher->hash('clan3'));
        $manager->persist($clan);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
