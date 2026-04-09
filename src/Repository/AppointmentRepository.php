<?php

namespace App\Repository;

use App\Entity\Appointment;
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
}
