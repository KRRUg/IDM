<?php

namespace App\Repository;

use App\Entity\User;
use App\Transfer\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Returns one User.
     *
     * @param array
     *
     * @return User|null Returns an User object or null if none could be found
     */
    public function findOneCaseInsensitive(array $criteria): ?User
    {
        $qb = $this->createQueryBuilder('u');

        foreach ($criteria as $k => $v) {
            $v = strtolower($v);
            $qb->andWhere($qb->expr()->like("LOWER(u.{$k})", ":{$k}"))->setParameter($k, $v);
        }

        $query = $qb->getQuery();
        return $query->getOneOrNullResult();
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
        // TODO add sort and paging
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function findAllActiveQueryBuilder(string $filter = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.nickname')
            ->where('u.status > 0');

        if (!empty($filter)) {
            $qb->andWhere('u.nickname like :q')
                ->setParameter('q', $filter."%");
        }

        return $qb;
    }
}
