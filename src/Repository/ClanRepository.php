<?php

namespace App\Repository;

use App\Entity\Clan;
use App\Helper\QueryHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

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

    /**
     * Returns one Clan. Search case insensitive.
     *
     * @param array
     *
     * @return Clan|null Returns a Clan object or null if none could be found
     */
    public function findOneByCi(array $criteria): ?Clan
    {
        $fields = $this->getEntityManager()->getClassMetadata(Clan::class)->getFieldNames();
        $criteria = $this->filterArray($criteria, $fields);

        $qb = $this->createQueryBuilder('c');

        foreach ($criteria as $k => $v) {
            $qb->andWhere($qb->expr()->eq("LOWER(c.{$k})", "LOWER(:{$k})"));
        }
        $qb
            ->setParameters($criteria)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns Clan objects. Search case insensitive.
     *
     * @param array
     *
     * @return mixed Returns the list of found Clan objects.
     */
    public function findByCi(array $criteria)
    {
        $fields = $this->getEntityManager()->getClassMetadata(Clan::class)->getFieldNames();
        $criteria = $this->filterArray($criteria, $fields);

        $qb = $this->createQueryBuilder('c');

        foreach ($criteria as $k => $v) {
            $qb->andWhere($qb->expr()->eq("LOWER(c.{$k})", "LOWER(:{$k})"));
        }
        $qb->setParameters($criteria);

        return $qb->getQuery()->getResult();
    }

    public function findAllSimpleQueryBuilder(?string $filter = null, array $sort = [], bool $exact = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $fields = $this->getEntityManager()->getClassMetadata(Clan::class)->getFieldNames();
        $sort = $this->filterArray($sort, $fields, ['asc', 'desc']);

        $parameter = $exact ?
            $this->makeLikeParam($filter, "%s") :
            $this->makeLikeParam($filter, "%%%s%%");

        if (!empty($filter)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    "LOWER(c.name) LIKE LOWER(:q) ESCAPE '!'",
                    "LOWER(c.clantag) LIKE LOWER(:q) ESCAPE '!'",
                )
            )->setParameter('q', $parameter);
        }

        if (empty($sort)) {
            $qb->orderBy('c.name');
        } else {
            foreach ($sort as $s => $d) {
                $qb->addOrderBy('c.'.$s, $d);
            }
        }

        return $qb;
    }

    public function findAllQueryBuilder(array $filter, array $sort = [], bool $exact = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $parameter = [];
        $criteria = [];
        $fields = $this->getEntityManager()->getClassMetadata(Clan::class)->getFieldNames();

        $filter = $this->filterArray($filter, $fields);
        $sort = $this->filterArray($sort, $fields, ['asc', 'desc']);

        foreach ($filter as $field => $value) {
            $parameter[$field] = $exact ?
                $this->makeLikeParam($value, "%s") :
                $this->makeLikeParam($value, "%%%s%%");
            $criteria[] = $exact ?
                "c.{$field} LIKE :{$field} ESCAPE '!'" :
                "LOWER(c.{$field}) LIKE LOWER(:{$field}) ESCAPE '!'";
        }

        $qb
            ->andWhere($qb->expr()->andX(...$criteria))
            ->setParameters($parameter);

        if (empty($sort)) {
            $qb->orderBy('c.nickname');
        } else {
            foreach ($sort as $field => $dir) {
                $qb->addOrderBy('c.'.$field, $dir);
            }
        }

        return $qb;
    }
}
