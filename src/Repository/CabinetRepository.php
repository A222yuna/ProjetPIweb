<?php

namespace App\Repository;

use App\Entity\Cabinet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Cabinet> */
class CabinetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cabinet::class);
    }

    /**
     * @return array{total:int, valides:int, enAttente:int}
     */
    public function getDashboardStats(): array
    {
        $total     = (int) $this->createQueryBuilder('c')->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
        $valides   = (int) $this->createQueryBuilder('c')->select('COUNT(c.id)')->where('c.valide = true AND c.archive = false')->getQuery()->getSingleScalarResult();
        $enAttente = (int) $this->createQueryBuilder('c')->select('COUNT(c.id)')->where('c.valide = false AND c.archive = false')->getQuery()->getSingleScalarResult();

        return [
            'total'     => $total,
            'valides'   => $valides,
            'enAttente' => $enAttente,
        ];
    }

    /**
     * @return Cabinet[]
     */
    public function findVisibleForPatients(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.valide = true')
            ->andWhere('c.archive = false')
            ->orderBy('c.ville', 'ASC');

        if ($search !== null && $search !== '') {
            $qb->andWhere('c.ville LIKE :q OR c.adresse LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }
    public function findRatingSummary(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id, c.ville AS ville, c.adresse AS adresse')
            ->addSelect('COALESCE(AVG(r.note), 0) AS avgNote')
            ->addSelect('COUNT(r.id) AS ratingCount')
            ->leftJoin('c.ratings', 'r')
            ->groupBy('c.id')
            ->orderBy('avgNote', 'DESC')
            ->addOrderBy('ratingCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
