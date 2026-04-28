<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\PsychologuePlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Appointment> */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @return Appointment[]
     */
    public function findForPsychologue(int $psychologueId): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')->addSelect('p')
            ->leftJoin('a.patient', 'pt')->addSelect('pt')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->andWhere('psy.id = :id')->setParameter('id', $psychologueId)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Appointment[]
     */
    public function findForPatient(int $patientId): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')->addSelect('p')
            ->leftJoin('a.patient', 'pt')->addSelect('pt')
            ->andWhere('pt.id = :id')->setParameter('id', $patientId)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: Appointment[], total:int}
     */
    public function findForPatientPaginated(int $patientId, int $page, int $perPage = 10): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')->addSelect('p')
            ->leftJoin('a.patient', 'pt')->addSelect('pt')
            ->andWhere('pt.id = :id')->setParameter('id', $patientId)
            ->orderBy('a.id', 'DESC');

        $items = (clone $qb)->setFirstResult($offset)->setMaxResults($perPage)->getQuery()->getResult();
        $total = (int) (clone $qb)->select('COUNT(a.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array{items: Appointment[], total:int}
     */
    public function findForPsychologuePaginated(int $psychologueId, int $page, int $perPage = 10): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')->addSelect('p')
            ->leftJoin('a.patient', 'pt')->addSelect('pt')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->andWhere('psy.id = :id')->setParameter('id', $psychologueId)
            ->orderBy('a.id', 'DESC');

        $items = (clone $qb)->setFirstResult($offset)->setMaxResults($perPage)->getQuery()->getResult();
        $total = (int) (clone $qb)->select('COUNT(a.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array{items: Appointment[], total:int}
     */
    public function findAdminPaginated(?string $status, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')->addSelect('p')
            ->leftJoin('a.patient', 'pt')->addSelect('pt')
            ->orderBy('a.id', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        $items = (clone $qb)->setFirstResult($offset)->setMaxResults($perPage)->getQuery()->getResult();
        $total = (int) (clone $qb)->select('COUNT(a.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        return ['items' => $items, 'total' => $total];
    }

    public function countScheduledForPlan(PsychologuePlan $plan): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.plan = :plan')->setParameter('plan', $plan)
            ->andWhere('a.status = :status')->setParameter('status', Appointment::STATUS_SCHEDULED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{items: Appointment[], total:int}
     */
    public function findAdminPaginatedWithDate(?string $status, ?\DateTimeInterface $date, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')->addSelect('p')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->leftJoin('a.patient', 'pt')->addSelect('pt')
            ->orderBy('a.createdAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }
        if ($date !== null) {
            $start = (new \DateTimeImmutable($date->format('Y-m-d')))->setTime(0, 0, 0);
            $end = $start->modify('+1 day');
            $qb->andWhere('a.createdAt >= :start')->setParameter('start', $start)
               ->andWhere('a.createdAt < :end')->setParameter('end', $end);
        }

        $items = (clone $qb)->setFirstResult($offset)->setMaxResults($perPage)->getQuery()->getResult();
        $total = (int) (clone $qb)->select('COUNT(a.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        return ['items' => $items, 'total' => $total];
    }

    public function findLatestNonCancelledForPatientAndPlan(User $patient, PsychologuePlan $plan): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.patient = :patient')->setParameter('patient', $patient)
            ->andWhere('a.plan = :plan')->setParameter('plan', $plan)
            ->andWhere('a.status != :cancelled')->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestForPatientAndPlanByStatuses(User $patient, PsychologuePlan $plan, array $statuses): ?Appointment
    {
        if ($statuses === []) {
            return null;
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.patient = :patient')->setParameter('patient', $patient)
            ->andWhere('a.plan = :plan')->setParameter('plan', $plan)
            ->andWhere('a.status IN (:statuses)')->setParameter('statuses', $statuses)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasActiveAppointmentForPatientAndPlan(User $patient, PsychologuePlan $plan): bool
    {
        $count = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.patient = :patient')->setParameter('patient', $patient)
            ->andWhere('a.plan = :plan')->setParameter('plan', $plan)
            ->andWhere('a.status = :status')->setParameter('status', Appointment::STATUS_SCHEDULED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countByStatusForPsy(int $psyId): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->leftJoin('a.plan', 'p')
            ->leftJoin('p.psychologue', 'psy')
            ->andWhere('psy.id = :id')
            ->setParameter('id', $psyId)
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();
    }

    public function countByMonthForPsy(int $psyId): array
    {
        $date = new \DateTimeImmutable('-12 months');
        return $this->createQueryBuilder('a')
            ->select('SUBSTRING(a.createdAt, 1, 7) as month, COUNT(a.id) as count')
            ->leftJoin('a.plan', 'p')
            ->leftJoin('p.psychologue', 'psy')
            ->andWhere('psy.id = :id')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('id', $psyId)
            ->setParameter('date', $date)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTopPatientsForPsy(int $psyId, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('pt.nom, pt.prenom, pt.email, COUNT(a.id) as count, MAX(a.createdAt) as lastAppointment')
            ->leftJoin('a.patient', 'pt')
            ->leftJoin('a.plan', 'p')
            ->leftJoin('p.psychologue', 'psy')
            ->andWhere('psy.id = :id')
            ->setParameter('id', $psyId)
            ->groupBy('pt.id')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByDayOfWeekForPsy(int $psyId): array
    {
        return $this->createQueryBuilder('a')
            ->select('p.dayOfWeek, COUNT(a.id) as count')
            ->leftJoin('a.plan', 'p')
            ->leftJoin('p.psychologue', 'psy')
            ->andWhere('psy.id = :id')
            ->setParameter('id', $psyId)
            ->groupBy('p.dayOfWeek')
            ->getQuery()
            ->getResult();
    }
}
