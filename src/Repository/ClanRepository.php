<?php

namespace App\Repository;

use App\Entity\Clan;
use App\Helper\QueryHelper;
use App\Transfer\Bulk;
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

    public function findByBulk(Bulk $bulk)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.uuid in (:uuids)')->setParameter('uuids', $bulk->uuid);
        $query = $qb->getQuery();
        return $query->getResult();
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
            $qb->orderBy('c.name');
        } else {
            foreach ($sort as $field => $dir) {
                $qb->addOrderBy('c.'.$field, $dir);
            }
        }

        return $qb;
    }
}
