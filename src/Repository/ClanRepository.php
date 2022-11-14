<?php

namespace App\Repository;

use App\Entity\Clan;
use App\Helper\QueryHelper;
use App\Transfer\Bulk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

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
     * Returns Clan objects. Search case-insensitive.
     *
     * @param array
     *
     * @return mixed returns the list of found Clan objects
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

    public function findAllSimpleQueryBuilder(?string $filter = null, array $sort = [], bool $case = false, bool $exact = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $fields = $this->getEntityManager()->getClassMetadata(Clan::class)->getFieldNames();
        $sort = $this->filterArray($sort, $fields, ['asc', 'desc']);

        $parameter = $exact ?
            $this->makeLikeParam($filter, '%s') :
            $this->makeLikeParam($filter, '%%%s%%');

        if (!empty($filter)) {
            $op = $case ? '' : 'LOWER';
            $qb->andWhere(
                $qb->expr()->orX(
                    "{$op}(c.name) LIKE {$op}(:q) ESCAPE '!'",
                    "{$op}(c.clantag) LIKE {$op}(:q) ESCAPE '!'",
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

    public function findAllQueryBuilder(array $filter, array $sort = [], bool $case = false, bool $exact = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $parameter = [];
        $criteria = [];
        $metadata = $this->getEntityManager()->getClassMetadata(Clan::class);
        $fields = $metadata->getFieldNames();

        $filter = $this->filterArray($filter, $fields);
        $sort = $this->filterArray($sort, $fields, ['asc', 'desc']);

        foreach ($filter as $field => $value) {
            switch ($metadata->getTypeOfField($field)) {
                case 'boolean':
                    $value = strtolower($value);
                    if (in_array($value, ['true', 'false', '1', '0'], true)) {
                        $criteria[] = "c.{$field} = :{$field}";
                        $parameter[$field] = $value == 'true' || $value == '1';
                    } else {
                        $criteria[] = '0=1';
                    }
                    break;
                case 'uuid':
                    if (Uuid::isValid($value)) {
                        $parameter[$field] = $value;
                        $criteria[] = "c.{$field} = :{$field}";
                    } else {
                        $criteria[] = '0=1';
                    }
                    break;
                case 'string':
                    $parameter[$field] = $exact ?
                        $this->makeLikeParam($value, '%s') :
                        $this->makeLikeParam($value, '%%%s%%');
                    $criteria[] = $case ?
                        "c.{$field} LIKE :{$field} ESCAPE '!'" :
                        "LOWER(c.{$field}) LIKE LOWER(:{$field}) ESCAPE '!'";
                    break;
                default:
                    $parameter[$field] = $value;
                    $criteria[] = "c.{$field} = :{$field}";
                    break;
            }
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
