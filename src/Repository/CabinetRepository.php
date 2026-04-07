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
}
