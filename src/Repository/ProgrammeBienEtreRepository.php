<?php

namespace App\Repository;

use App\Entity\ProgrammeBienEtre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ProgrammeBienEtre> */
class ProgrammeBienEtreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgrammeBienEtre::class);
    }

    /**
     * @return ProgrammeBienEtre[]
     */
    public function findFrontCatalog(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->leftJoin('p.activites', 'a')->addSelect('a')
            ->leftJoin('p.avis', 'av')->addSelect('av')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
