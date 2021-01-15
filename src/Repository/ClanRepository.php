<?php

namespace App\Repository;

use App\Entity\Clan;
use App\Helper\QueryHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Clan|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clan|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clan[]    findAll()
 * @method Clan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClanRepository extends ServiceEntityRepository
{
    use QueryHelper;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clan::class);
    }

    /**
     * Returns all Clans but only with active Users.
     *
     * @return Clan[] Returns an array of Clan objects
     */
    public function findAllWithActiveUsers(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->select('u', 'c', 'userclan')
            ->innerJoin('c.users', 'userclan')
            ->innerJoin('userclan.user', 'u')
            ->where($qb->expr()->gte('u.status', 1));

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Returns one Clan but only with active Users.
     *
     * @param $uuid
     *
     * @return Clan|null Returns a Clan object or null if none could be found
     */
    public function findOneWithActiveUsersByUuid(string $uuid): ?Clan
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->select('u', 'c', 'userclan')
            ->innerJoin('c.users', 'userclan')
            ->innerJoin('userclan.user', 'u')
            ->where($qb->expr()->gte('u.status', 1))
            ->andWhere($qb->expr()->eq('c.uuid', ':uuid'))
            ->setParameter('uuid', $uuid);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Returns one Clan.
     *
     * @param array
     *
     * @return Clan|null Returns a Clan object or null if none could be found
     */
    public function findOneByLowercase(array $criteria): ?Clan
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->select('u', 'c', 'userclan')
            ->innerJoin('c.users', 'userclan')
            ->innerJoin('userclan.user', 'u');

        foreach ($criteria as $k => $v) {
            $v = strtolower($v);
            $qb->andWhere($qb->expr()->like("LOWER(c.{$k})", ":{$k}"));
            $qb->setParameter($k, $v);

        }

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Returns all Clans but don't return Data from User Relations.
     *
     * @return Clan[] Returns an array of Clan objects
     */
    public function findAllWithoutUserRelations(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.clantag', 'c.createdAt', 'c.description', 'c.modifiedAt', 'c.name', 'c.uuid', 'c.website');

        $query = $qb->getQuery();

        return $query->execute();
    }

    public function findAllWithActiveUsersQueryBuilder(string $filter = null)
    {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->select('c', 'uc', 'u')
            ->leftJoin('c.users', 'uc')
            ->leftJoin('uc.user', 'u')
            ->where('u is null or u.status >= 1')
            ->orderBy('c.name');

        if (!empty($filter)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:q)')
                ->setParameter('q', "%".$filter."%");
        }

        return $qb;
    }
}
