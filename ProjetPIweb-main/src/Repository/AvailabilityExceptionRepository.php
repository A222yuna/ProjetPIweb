<?php

namespace App\Repository;

use App\Entity\AvailabilityException;
use App\Entity\Cabinet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AvailabilityException> */
class AvailabilityExceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityException::class);
    }

    /**
     * Check if a given datetime range overlaps any blocking period for a cabinet.
     */
    public function isBlocked(Cabinet $cabinet, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.cabinet = :cabinet')
            ->andWhere('e.dateDebut < :end')
            ->andWhere('e.dateFin > :start')
            ->setParameter('cabinet', $cabinet)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return AvailabilityException[]
     */
    public function findByCabinet(Cabinet $cabinet): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.cabinet = :cabinet')
            ->setParameter('cabinet', $cabinet)
            ->orderBy('e.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
