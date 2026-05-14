<?php

namespace App\Repository;

use App\Entity\ForumReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumReport>
 */
class ForumReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumReport::class);
    }

    /**
     * @return array{items: ForumReport[], total:int}
     */
    public function findAdminPaginated(?string $query, ?string $status, ?string $type, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.reporter', 'rep')->addSelect('rep')
            ->leftJoin('r.targetPost', 'p')->addSelect('p')
            ->leftJoin('r.targetComment', 'c')->addSelect('c')
            ->leftJoin('c.post', 'cp')->addSelect('cp')
            ->orderBy('r.createdAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        if ($type === 'post') {
            $qb->andWhere('r.targetPost IS NOT NULL');
        } elseif ($type === 'comment') {
            $qb->andWhere('r.targetComment IS NOT NULL');
        }

        if ($query !== null && $query !== '') {
            $q = '%'.mb_strtolower($query).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(r.reason) LIKE :q',
                'LOWER(r.details) LIKE :q',
                'LOWER(p.titre) LIKE :q',
                'LOWER(p.contenu) LIKE :q',
                'LOWER(c.contenu) LIKE :q'
            ))->setParameter('q', $q);
        }

        $items = (clone $qb)
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $totalCountQb = clone $qb;
        $totalCountQb->select('COUNT(DISTINCT r.id)');
        $total = (int) $totalCountQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}

