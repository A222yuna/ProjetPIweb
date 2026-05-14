<?php

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\User;
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
        $totalCountQb->select('COUNT(DISTINCT c.id)');
        $total = (int) $totalCountQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @return Commentaire[]
     */
    public function findLatestCommentsOnUserPosts(User $user, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));

        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')->addSelect('p')
            ->leftJoin('c.auteur', 'a')->addSelect('a')
            ->andWhere('p.auteur = :user')->setParameter('user', $user)
            ->andWhere('c.auteur != :user')->setParameter('user', $user)
            ->andWhere('p.isHidden = 0')
            ->andWhere('c.isHidden = 0')
            ->orderBy('c.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
