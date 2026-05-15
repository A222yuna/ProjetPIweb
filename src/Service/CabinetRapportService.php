<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class CabinetRapportService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getCabinetStats(int $cabinetId): array
    {
        $conn = $this->em->getConnection();

        // Total creneaux for this cabinet (via disponibilite → cabinet)
        $totalCreneaux = (int) $this->em->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :id'
        )->setParameter('id', $cabinetId)->getSingleScalarResult();

        // Creneaux per month (last 6 months) — native SQL because MONTH() is not in DQL
        $creneauxPerMonth = $conn->executeQuery(
            'SELECT MONTH(c.date_creneau) AS mois, COUNT(c.id) AS total
             FROM creneau c
             JOIN disponibilite d ON c.disponibilite_id = d.id
             WHERE d.cabinet_id = :id
               AND c.date_creneau >= :sixMonthsAgo
             GROUP BY MONTH(c.date_creneau)
             ORDER BY mois ASC',
            [
                'id'           => $cabinetId,
                'sixMonthsAgo' => (new \DateTime('-6 months'))->format('Y-m-d'),
            ]
        )->fetchAllAssociative();

        // Reserved creneaux
        $reservedCreneaux = (int) $this->em->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :id AND c.statut = :statut'
        )
        ->setParameter('id', $cabinetId)
        ->setParameter('statut', 'RESERVE')
        ->getSingleScalarResult();

        // Cancelled creneaux
        $cancelledCreneaux = (int) $this->em->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :id AND c.statut = :statut'
        )
        ->setParameter('id', $cabinetId)
        ->setParameter('statut', 'ANNULE')
        ->getSingleScalarResult();

        // Total disponibilites for this cabinet
        $totalDisponibilites = (int) $this->em->createQuery(
            'SELECT COUNT(d.id)
             FROM App\Entity\Disponibilite d
             WHERE d.cabinet = :id'
        )->setParameter('id', $cabinetId)->getSingleScalarResult();

        // Average rating
        $avgRating = $this->em->createQuery(
            'SELECT AVG(r.note)
             FROM App\Entity\Rating r
             WHERE r.cabinet = :id'
        )->setParameter('id', $cabinetId)->getSingleScalarResult();

        // Total ratings count
        $totalRatings = (int) $this->em->createQuery(
            'SELECT COUNT(r.id)
             FROM App\Entity\Rating r
             WHERE r.cabinet = :id'
        )->setParameter('id', $cabinetId)->getSingleScalarResult();

        $occupationRate = $totalCreneaux > 0
            ? round(($reservedCreneaux / $totalCreneaux) * 100, 1)
            : 0;

        return [
            'total_creneaux'       => $totalCreneaux,
            'reserved_creneaux'    => $reservedCreneaux,
            'cancelled_creneaux'   => $cancelledCreneaux,
            'total_disponibilites' => $totalDisponibilites,
            'creneaux_monthly'     => $creneauxPerMonth,
            'occupation_rate'      => $occupationRate,
            'avg_rating'           => round((float) $avgRating, 1),
            'total_ratings'        => $totalRatings,
            'estimated_revenue'    => $reservedCreneaux * 50,
            'generated_at'         => (new \DateTime())->format('d/m/Y H:i'),
        ];
    }
}
