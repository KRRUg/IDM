<?php

namespace App\Repository;

use App\Entity\User;
use App\Helper\QueryHelper;
use App\Transfer\Bulk;
use App\Transfer\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    use QueryHelper;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Returns one User. Search case-insensitive.
     *
     * @param array
     *
     * @return User|null Returns a User object or null if none could be found
     */
    public function findOneByCi(array $criteria): ?User
    {
        $fields = $this->getEntityManager()->getClassMetadata(User::class)->getFieldNames();
        $criteria = $this->filterArray($criteria, $fields);

        $qb = $this->createQueryBuilder('u');

        foreach ($criteria as $k => $v) {
            $qb->andWhere($qb->expr()->eq("LOWER(u.{$k})", "LOWER(:{$k})"));
        }
        $qb
            ->setParameters($criteria)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns User objects. Search case-insensitive.
     *
     * @param array
     *
     * @return mixed returns the list of found User objects
     */
    public function findByCi(array $criteria)
    {
        $fields = $this->getEntityManager()->getClassMetadata(User::class)->getFieldNames();
        $criteria = $this->filterArray($criteria, $fields);

        $qb = $this->createQueryBuilder('u');

        foreach ($criteria as $k => $v) {
            $qb->andWhere($qb->expr()->eq("LOWER(u.{$k})", "LOWER(:{$k})"));
        }
        $qb->setParameters($criteria);

        return $qb->getQuery()->getResult();
    }

    public function findByBulk(Bulk $bulk)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->andWhere('u.uuid in (:uuids)')->setParameter('uuids', $bulk->uuid);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findBySearch(Search $search)
    {
        $qb = $this->createQueryBuilder('u');
        if (!empty($search->uuid)) {
            $qb->andWhere('u.uuid in (:uuids)')->setParameter('uuids', $search->uuid);
        }
        if (!is_null($search->nickname)) {
            $qb->andWhere('u.nickname = :nick')->setParameter('nick', $search->nickname);
        }
        if (!is_null($search->superadmin)) {
            $qb->andWhere('u.isSuperadmin = :su')->setParameter('su', $search->superadmin);
        }
        if (!is_null($search->newsletter)) {
            $qb->andWhere('u.infoMails = :mail')->setParameter('mail', $search->newsletter);
        }
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findAllSimpleQueryBuilder(?string $filter = null,
                                              array $sort = [],
                                              bool $case = false,
                                              bool $exact = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $fields = $this->getEntityManager()->getClassMetadata(User::class)->getFieldNames();
        $sort = $this->filterArray($sort, $fields, ['asc', 'desc']);

        $parameter = $exact ?
            $this->makeLikeParam($filter, '%s') :
            $this->makeLikeParam($filter, '%%%s%%');

        if (!empty($filter)) {
            $op = $case ? '' : 'LOWER';
            $qb->andWhere(
                $qb->expr()->orX(
                    "{$op}(u.nickname) LIKE {$op}(:q) ESCAPE '!'",
                    "{$op}(u.email) LIKE {$op}(:q) ESCAPE '!'",
                    "{$op}(u.surname) LIKE {$op}(:q) ESCAPE '!'",
                    "{$op}(u.firstname) LIKE {$op}(:q) ESCAPE '!'",
                )
            )->setParameter('q', $parameter);
        }

        if (empty($sort)) {
            $qb->orderBy('u.nickname');
        } else {
            foreach ($sort as $s => $d) {
                $qb->addOrderBy('u.'.$s, $d);
            }
        }

        return $qb;
    }

    public function findAllQueryBuilder(array $filter,
                                        array $sort = [],
                                        bool $case = false,
                                        bool $exact = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $parameter = [];
        $criteria = [];
        $metadata = $this->getEntityManager()->getClassMetadata(User::class);
        $fields = $metadata->getFieldNames();

        $filter = $this->filterArray($filter, $fields);
        $sort = $this->filterArray($sort, $fields, ['asc', 'desc']);

        foreach ($filter as $field => $value) {
            switch ($metadata->getTypeOfField($field)) {
                case 'boolean':
                    $value = strtolower((string) $value);
                    if (in_array($value, ['true', 'false', '1', '0'], true)) {
                        $criteria[] = "u.{$field} = :{$field}";
                        $parameter[$field] = $value == 'true' || $value == '1';
                    } else {
                        $criteria[] = '0=1';
                    }
                    break;
                case 'uuid':
                    if (Uuid::isValid($value)) {
                        $parameter[$field] = $value;
                        $criteria[] = "u.{$field} = :{$field}";
                    } else {
                        $criteria[] = '0=1';
                    }
                    break;
                case 'string':
                    $parameter[$field] = $exact ?
                        $this->makeLikeParam($value, '%s') :
                        $this->makeLikeParam($value, '%%%s%%');
                    $criteria[] = $case ?
                        "u.{$field} LIKE :{$field} ESCAPE '!'" :
                        "LOWER(u.{$field}) LIKE LOWER(:{$field}) ESCAPE '!'";
                    break;
                default:
                    $parameter[$field] = $value;
                    $criteria[] = "u.{$field} = :{$field}";
                    break;
            }
        }

        $qb
            ->andWhere($qb->expr()->andX(...$criteria))
            ->setParameters($parameter);

        if (empty($sort)) {
            $qb->orderBy('u.nickname');
        } else {
            foreach ($sort as $field => $dir) {
                $qb->addOrderBy('u.'.$field, $dir);
            }
        }

        return $qb;
    }
}
