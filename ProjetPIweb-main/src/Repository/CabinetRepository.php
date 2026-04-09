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
     * @return list<array{id:int, ville:string, adresse:string, avgNote:float, ratingCount:int}>
     */
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

    /**
     * @return array{total:int, valides:int, enAttente:int}
     */
    public function getDashboardStats(): array
    {
        $total = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $valides = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.valide = :valide')
            ->andWhere('c.archive = :archive')
            ->setParameter('valide', true)
            ->setParameter('archive', false)
            ->getQuery()
            ->getSingleScalarResult();

        $enAttente = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.valide = :valide')
            ->andWhere('c.archive = :archive')
            ->setParameter('valide', false)
            ->setParameter('archive', false)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'valides' => $valides,
            'enAttente' => $enAttente,
        ];
    }

    /**
     * @return Cabinet[]
     */
    public function findVisibleForPatients(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.archive = :archive')
            ->andWhere('c.valide = :valide')
            ->setParameter('archive', false)
            ->setParameter('valide', true)
            ->orderBy('c.id', 'DESC');

        if ($search !== null && $search !== '') {
            $term = '%'.mb_strtolower($search).'%';
            $qb
                ->andWhere('LOWER(c.ville) LIKE :term OR LOWER(c.adresse) LIKE :term OR LOWER(c.description) LIKE :term')
                ->setParameter('term', $term);
        }

        return $qb->getQuery()->getResult();
    }
}
