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

    /** @return PsychologuePlan[] */
    public function findForPsychologue(User $psychologue): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.psychologue = :psy')->setParameter('psy', $psychologue)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche + tri dynamique
     * @return PsychologuePlan[]
     */
    public function findForPsychologueFiltered(
        User $psychologue,
        string $search = '',
        string $sortBy = 'createdAt',
        string $sortDir = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.psychologue = :psy')
            ->setParameter('psy', $psychologue);

        // RECHERCHE sur jour et période
        if ($search !== '') {
            $qb->andWhere('p.dayOfWeek LIKE :q OR p.period LIKE :q')
               ->setParameter('q', '%'.$search.'%');
        }

        $allowedSorts = ['dayOfWeek', 'period', 'maxAppointments', 'createdAt'];
        $sortBy = \in_array($sortBy, $allowedSorts, true) ? $sortBy : 'createdAt';
        $sortDir = $sortDir === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('p.'.$sortBy, $sortDir);

        return $qb->getQuery()->getResult();
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