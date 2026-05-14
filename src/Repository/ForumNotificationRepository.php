<?php

namespace App\Repository;

use App\Entity\ForumNotification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumNotification>
 */
class ForumNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumNotification::class);
    }

    /** @return ForumNotification[] */
    public function findForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.comment', 'c')->addSelect('c')
            ->leftJoin('n.post', 'p')->addSelect('p')
            ->leftJoin('c.auteur', 'a')->addSelect('a')
            ->andWhere('n.recipient = :user')->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')->setParameter('user', $user)
            ->andWhere('n.isRead = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllReadForUser(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->andWhere('n.recipient = :user')->setParameter('user', $user)
            ->andWhere('n.isRead = false')
            ->getQuery()
            ->execute();
    }
}
