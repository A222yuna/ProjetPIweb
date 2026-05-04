<?php

namespace App\Repository;

use App\Entity\Creneau;
use App\Entity\Disponibilite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Creneau> */
class CreneauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Creneau::class);
    }

    /** @return Creneau[] */
    public function findForPatient(User $patient): array
    {
        return $this->createQueryBuilder('cr')
            ->leftJoin('cr.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.cabinet', 'c')->addSelect('c')
            ->leftJoin('c.psyCabinets', 'pc')->addSelect('pc')
            ->leftJoin('pc.psychologue', 'psy')->addSelect('psy')
            ->andWhere('cr.patient = :p')->setParameter('p', $patient)
            ->orderBy('cr.dateCreneau', 'DESC')
            ->addOrderBy('cr.heure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche + filtre statut + tri + pagination
     * @return array{items: Creneau[], total: int}
     */
    public function findForPatientPaginatedFiltered(
        User $patient,
        string $search = '',
        string $filterStatut = '',
        string $sortBy = 'dateCreneau',
        string $sortDir = 'DESC',
        int $page = 1,
        int $perPage = 8
    ): array {
        $allowedSorts = ['dateCreneau', 'heure', 'statut'];
        $sortBy  = \in_array($sortBy, $allowedSorts, true) ? $sortBy : 'dateCreneau';
        $sortDir = $sortDir === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('cr')
            ->leftJoin('cr.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.cabinet', 'c')->addSelect('c')
            ->leftJoin('c.psyCabinets', 'pc')->addSelect('pc')
            ->leftJoin('pc.psychologue', 'psy')->addSelect('psy')
            ->andWhere('cr.patient = :p')
            ->setParameter('p', $patient);

        // FILTRE statut
        if ($filterStatut !== '') {
            $qb->andWhere('cr.statut = :statut')->setParameter('statut', $filterStatut);
        }

        // RECHERCHE sur ville cabinet ou nom psychologue
        if ($search !== '') {
            $qb->andWhere(
                'c.ville LIKE :q OR psy.nom LIKE :q OR psy.prenom LIKE :q OR CAST(cr.dateCreneau AS string) LIKE :q'
            )->setParameter('q', '%'.$search.'%');
        }

        $qb->orderBy('cr.'.$sortBy, $sortDir);

        $total = (int)(clone $qb)
            ->select('COUNT(cr.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $items = (clone $qb)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function isSlotAlreadyBooked(Disponibilite $disponibilite, \DateTimeInterface $date, \DateTimeInterface $heure): bool
    {
        $count = (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->andWhere('cr.disponibilite = :d')->setParameter('d', $disponibilite)
            ->andWhere('cr.dateCreneau = :date')->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->andWhere('cr.heure = :heure')->setParameter('heure', $heure, Types::TIME_IMMUTABLE)
            ->andWhere('cr.statut != :annule')->setParameter('annule', Creneau::STATUT_ANNULE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function hasAppointmentOnDay(User $patient, \DateTimeInterface $date): bool
    {
        $count = (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->andWhere('cr.patient = :p')->setParameter('p', $patient)
            ->andWhere('cr.dateCreneau = :date')->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->andWhere('cr.statut != :annule')->setParameter('annule', Creneau::STATUT_ANNULE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}