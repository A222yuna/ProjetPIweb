<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'u')->addSelect('u')
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneWithComments(int $id): ?Post
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'a')->addSelect('a')
            ->leftJoin('p.commentaires', 'c')->addSelect('c')
            ->leftJoin('c.auteur', 'ca')->addSelect('ca')
            ->leftJoin('c.parent', 'cp')->addSelect('cp')
            ->leftJoin('c.replies', 'cr')->addSelect('cr')
            ->leftJoin('cr.auteur', 'cra')->addSelect('cra')
            ->andWhere('p.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche texte sur titre / contenu + filtre catégorie exacte.
     *
     * @return Post[]
     */
    public function searchConsultations(?string $query, ?string $categorie): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'u')->addSelect('u')
            ->orderBy('p.date', 'DESC');

        if ($query !== null && $query !== '') {
            $qb->andWhere($qb->expr()->orX(
                'LOWER(p.titre) LIKE :q',
                'LOWER(p.contenu) LIKE :q'
            ))->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('p.categorie = :cat')->setParameter('cat', $categorie);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{items: Post[], total:int}
     */
    public function searchConsultationsPaginated(?string $query, ?string $categorie, int $page, int $perPage = 6, ?string $sortBy = 'recent'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'u')->addSelect('u')
            ->leftJoin('p.commentaires', 'c');

        if ($query !== null && $query !== '') {
            $qb->andWhere($qb->expr()->orX(
                'LOWER(p.titre) LIKE :q',
                'LOWER(p.contenu) LIKE :q'
            ))->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('p.categorie = :cat')->setParameter('cat', $categorie);
        }

        // Logic for sorting
        switch ($sortBy) {
            case 'likes':
                $qb->orderBy('p.nbLikes', 'DESC');
                break;
            case 'comments':
                $qb->addSelect('COUNT(c.id_comment) AS HIDDEN commentCount')
                   ->groupBy('p.id_post')
                   ->orderBy('commentCount', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('p.date', 'DESC');
                break;
        }

        $items = (clone $qb)
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $totalCountQb = clone $qb;
        $totalCountQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $totalCountQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @return list<string>
     */
    public function findDistinctCategories(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.categorie AS c')
            ->orderBy('c', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_filter($rows, static fn ($v) => $v !== null && $v !== ''));
    }
}
