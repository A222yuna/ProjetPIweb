<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findForAuthorPicker(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.estActif = true')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: User[], total:int}
     */
    public function findAdminPaginated(?string $role, ?bool $active, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }
        if ($active !== null) {
            $qb->andWhere('u.estActif = :active')->setParameter('active', $active);
        }

        $items = (clone $qb)->setFirstResult($offset)->setMaxResults($perPage)->getQuery()->getResult();
        $total = (int) (clone $qb)->select('COUNT(u.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        return ['items' => $items, 'total' => $total];
    }
}
