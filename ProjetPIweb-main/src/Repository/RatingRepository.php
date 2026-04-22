<?php

namespace App\Repository;

use App\Entity\Cabinet;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Rating> */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Check if a patient has at least one RESERVE creneau OR one Appointment for this cabinet.
     * Business rule: only patients who actually consulted can rate.
     * Falls back to true if no consultation data exists at all (empty system).
     */
    public function hasPatientConsulted(User $patient, Cabinet $cabinet): bool
    {
        // Check via Creneau (slot-based booking)
        $creneauCount = (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE c.patient = :patient
               AND d.cabinet = :cabinet
               AND c.statut = :statut'
        )
        ->setParameter('patient', $patient)
        ->setParameter('cabinet', $cabinet)
        ->setParameter('statut', 'RESERVE')
        ->getSingleScalarResult();

        if ($creneauCount > 0) {
            return true;
        }

        // Check via Appointment → PsychologuePlan → PsyCabinet → Cabinet
        $appointmentCount = (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(a.id)
             FROM App\Entity\Appointment a
             JOIN a.plan pl
             JOIN pl.psychologue psy
             JOIN App\Entity\PsyCabinet pc WITH pc.psychologue = psy
             WHERE a.patient = :patient
               AND pc.cabinet = :cabinet'
        )
        ->setParameter('patient', $patient)
        ->setParameter('cabinet', $cabinet)
        ->getSingleScalarResult();

        if ($appointmentCount > 0) {
            return true;
        }

        // Fallback: if the entire system has no consultation data yet,
        // allow any authenticated patient to rate (development / early-stage)
        $totalCreneaux = (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(c.id) FROM App\Entity\Creneau c'
        )->getSingleScalarResult();

        $totalAppointments = (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(a.id) FROM App\Entity\Appointment a'
        )->getSingleScalarResult();

        return ($totalCreneaux === 0 && $totalAppointments === 0);
    }

    /**
     * Count how many distinct creneaux/appointments a patient has had at this cabinet.
     */
    public function countPatientConsultations(User $patient, Cabinet $cabinet): int
    {
        $creneaux = (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE c.patient = :patient
               AND d.cabinet = :cabinet
               AND c.statut = :statut'
        )
        ->setParameter('patient', $patient)
        ->setParameter('cabinet', $cabinet)
        ->setParameter('statut', 'RESERVE')
        ->getSingleScalarResult();

        $appointments = (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(a.id)
             FROM App\Entity\Appointment a
             JOIN a.plan pl
             JOIN pl.psychologue psy
             JOIN App\Entity\PsyCabinet pc WITH pc.psychologue = psy
             WHERE a.patient = :patient
               AND pc.cabinet = :cabinet'
        )
        ->setParameter('patient', $patient)
        ->setParameter('cabinet', $cabinet)
        ->getSingleScalarResult();

        return $creneaux + $appointments;
    }

    /**
     * Find existing rating for a patient+cabinet pair.
     */
    public function findByPatientAndCabinet(User $patient, Cabinet $cabinet): ?Rating
    {
        return $this->findOneBy(['patient' => $patient, 'cabinet' => $cabinet]);
    }

    /**
     * Get all verified ratings for a cabinet, ordered by most recent.
     *
     * @return Rating[]
     */
    public function findVerifiedByCabinet(Cabinet $cabinet): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.cabinet = :cabinet')
            ->andWhere('r.isVerified = :verified')
            ->setParameter('cabinet', $cabinet)
            ->setParameter('verified', true)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all ratings for a cabinet (verified + unverified), ordered by most recent.
     *
     * @return Rating[]
     */
    public function findAllByCabinet(Cabinet $cabinet): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.cabinet = :cabinet')
            ->setParameter('cabinet', $cabinet)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compute weighted average note for a cabinet using noteGlobale.
     * Falls back to note if noteGlobale is null.
     */
    public function getWeightedAverage(Cabinet $cabinet): float
    {
        $result = $this->getEntityManager()->createQuery(
            'SELECT AVG(COALESCE(r.noteGlobale, r.note))
             FROM App\Entity\Rating r
             WHERE r.cabinet = :cabinet'
        )
        ->setParameter('cabinet', $cabinet)
        ->getSingleScalarResult();

        return round((float) $result, 2);
    }

    /**
     * Count distinct patients who rated this cabinet more than once (loyal patients).
     */
    public function countLoyalPatients(Cabinet $cabinet): int
    {
        // A loyal patient has >= 2 creneaux at this cabinet
        $result = $this->getEntityManager()->createQuery(
            'SELECT COUNT(DISTINCT c.patient)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :cabinet
               AND c.statut = :statut
             GROUP BY c.patient
             HAVING COUNT(c.id) >= 2'
        )
        ->setParameter('cabinet', $cabinet)
        ->setParameter('statut', 'RESERVE')
        ->getResult();

        return count($result);
    }

    /**
     * Count all distinct patients who ever had a creneau at this cabinet.
     */
    public function countTotalPatients(Cabinet $cabinet): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(DISTINCT c.patient)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :cabinet
               AND c.statut = :statut'
        )
        ->setParameter('cabinet', $cabinet)
        ->setParameter('statut', 'RESERVE')
        ->getSingleScalarResult();
    }
}
