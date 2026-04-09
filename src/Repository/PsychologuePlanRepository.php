<?php

namespace App\Repository;

use App\Entity\PsychologuePlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PsychologuePlan> */
class PsychologuePlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PsychologuePlan::class);
    }

    /**
     * @return PsychologuePlan[]
     */
    public function findForPsychologue(User $psychologue): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.psychologue = :psy')->setParameter('psy', $psychologue)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existsDuplicatePlan(User $psychologue, string $dayOfWeek, string $period, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.psychologue = :psy')->setParameter('psy', $psychologue)
            ->andWhere('p.dayOfWeek = :dow')->setParameter('dow', $dayOfWeek)
            ->andWhere('p.period = :period')->setParameter('period', $period);

        if ($excludeId !== null) {
            $qb->andWhere('p.id != :exclude')->setParameter('exclude', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findOneForPsychologueDayPeriod(User $psychologue, string $dayOfWeek, string $period): ?PsychologuePlan
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.psychologue = :psy')->setParameter('psy', $psychologue)
            ->andWhere('p.dayOfWeek = :dow')->setParameter('dow', $dayOfWeek)
            ->andWhere('p.period = :period')->setParameter('period', $period)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
