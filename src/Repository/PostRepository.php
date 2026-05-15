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
            ->andWhere('p.isHidden = 0')
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneWithComments(int $id, bool $includeHidden = false): ?Post
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'a')->addSelect('a');

        if ($includeHidden) {
            $qb->leftJoin('p.commentaires', 'c')->addSelect('c');
        } else {
            $qb->andWhere('p.isHidden = 0')
               ->leftJoin('p.commentaires', 'c', 'WITH', 'c.isHidden = 0')->addSelect('c');
        }

        return $qb
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
    public function searchConsultations(?string $query, ?string $categorie, bool $includeHidden = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'u')->addSelect('u')
            ->orderBy('p.date', 'DESC');

        if (!$includeHidden) {
            $qb->andWhere('p.isHidden = 0');
        }

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
    public function searchConsultationsPaginated(?string $query, ?string $categorie, int $page, int $perPage = 6, ?string $sortBy = 'recent', bool $includeHidden = false): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // Base query without sorting
        $baseQb = $this->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'u')->addSelect('u');

        if (!$includeHidden) {
            $baseQb->andWhere('p.isHidden = 0');
        }

        if ($query !== null && $query !== '') {
            $baseQb->andWhere($baseQb->expr()->orX(
                'LOWER(p.titre) LIKE :q',
                'LOWER(p.contenu) LIKE :q'
            ))->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        if ($categorie !== null && $categorie !== '') {
            $baseQb->andWhere('p.categorie = :cat')->setParameter('cat', $categorie);
        }

        // Get total count first
        $totalCountQb = clone $baseQb;
        $totalCountQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $totalCountQb->getQuery()->getSingleScalarResult();

        // Apply sorting
        switch ($sortBy) {
            case 'likes':
                $baseQb->orderBy('p.nbLikes', 'DESC');
                break;
            case 'comments':
                // Use a subquery to get posts with comment counts, then order by it
                $baseQb->addSelect('(SELECT COUNT(c2.id) FROM App\Entity\Commentaire c2 WHERE c2.post = p) AS HIDDEN commentCount')
                   ->orderBy('commentCount', 'DESC');
                break;
            case 'recent':
            default:
                $baseQb->orderBy('p.date', 'DESC');
                break;
        }

        // Apply pagination and get results
        $items = $baseQb
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

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
