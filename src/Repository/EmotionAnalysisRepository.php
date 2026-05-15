<?php

namespace App\Repository;

use App\Entity\Cabinet;
use App\Entity\EmotionAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmotionAnalysis> */
class EmotionAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmotionAnalysis::class);
    }

    public function findLatestByCabinet(Cabinet $cabinet): ?EmotionAnalysis
    {
        return $this->createQueryBuilder('e')
            ->where('e.cabinet = :cabinet')
            ->setParameter('cabinet', $cabinet)
            ->orderBy('e.analysedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return EmotionAnalysis[] */
    public function findAllLatest(): array
    {
        // One per cabinet, most recent
        return $this->createQueryBuilder('e')
            ->orderBy('e.analysedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
