<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * @return array{items: Commentaire[], total:int}
     */
    public function findAdminPaginated(?string $query, int $page, int $perPage = 15, ?string $sortBy = 'recent'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')->addSelect('p')
            ->leftJoin('c.auteur', 'u')->addSelect('u');

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(c.contenu) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        switch ($sortBy) {
            case 'likes':
                $qb->orderBy('c.nbLikes', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('c.date', 'DESC');
                break;
        }

        $items = (clone $qb)
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $totalCountQb = clone $qb;
        $totalCountQb->select('COUNT(DISTINCT c.id_comment)');
        $total = (int) $totalCountQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
