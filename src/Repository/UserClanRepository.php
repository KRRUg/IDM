<?php

namespace App\Repository;

use App\Entity\UserClan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserClan|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserClan|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserClan[]    findAll()
 * @method UserClan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserClanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserClan::class);
    }

    /**
     * Returns all Admins in a Clan by ClanUUID.
     *
     * @return UserClan[] Returns an array of UserClan objects
     */
    public function findAllAdminsByClanUuid(string $uuid): array
    {
        $qb = $this->createQueryBuilder('user_clan');
        $qb
            ->select('user_clan')
            ->innerJoin('user_clan.clan', 'clan')
            ->where($qb->expr()->eq('clan.uuid', ':uuid'))
            ->andWhere($qb->expr()->eq('user_clan.admin', ':admin'))
            ->setParameter('uuid', $uuid)
            ->setParameter('admin', true);

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Returns one ClanUser in a Clan by ClanUUID and UserUUID.
     *
     * @return UserClan|null Returns a UserClan Object
     *
     * @throws NonUniqueResultException
     */
    public function findOneClanUserByUuid(string $clanuuid, string $useruuid): ?UserClan
    {
        $qb = $this->createQueryBuilder('user_clan');
        $qb
            ->select('user_clan')
            ->innerJoin('user_clan.clan', 'clan')
            ->innerJoin('user_clan.user', 'user')
            ->where($qb->expr()->eq('clan.uuid', ':clanuuid'))
            ->andWhere($qb->expr()->eq('user.uuid', ':useruuid'))
            ->setParameter('clanuuid', $clanuuid)
            ->setParameter('useruuid', $useruuid);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }
}
