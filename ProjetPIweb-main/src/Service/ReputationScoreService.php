<?php

namespace App\Service;

use App\Entity\Cabinet;
use Doctrine\ORM\EntityManagerInterface;

class ReputationScoreService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function calculateScore(Cabinet $cabinet): array
    {
        // --- CRITÈRE 1: Moyenne des avis patients (35%) ---
        $avgRating = $this->em->createQuery(
            'SELECT AVG(r.note) FROM App\Entity\Rating r WHERE r.cabinet = :cabinet'
        )->setParameter('cabinet', $cabinet)->getSingleScalarResult();

        $ratingScore = $avgRating ? ($avgRating / 5) * 100 : 0;

        // --- CRITÈRE 2: Taux de complétion des RDV (25%) ---
        // Appointment → plan → psychologue → PsyCabinet → cabinet
        $totalAppts = $this->em->createQuery(
            'SELECT COUNT(a.id)
             FROM App\Entity\Appointment a
             JOIN a.plan pl
             JOIN pl.psychologue psy
             JOIN App\Entity\PsyCabinet pc WITH pc.psychologue = psy
             WHERE pc.cabinet = :cabinet'
        )->setParameter('cabinet', $cabinet)->getSingleScalarResult();

        $completedAppts = $this->em->createQuery(
            'SELECT COUNT(a.id)
             FROM App\Entity\Appointment a
             JOIN a.plan pl
             JOIN pl.psychologue psy
             JOIN App\Entity\PsyCabinet pc WITH pc.psychologue = psy
             WHERE pc.cabinet = :cabinet AND a.status = :status'
        )->setParameter('cabinet', $cabinet)
         ->setParameter('status', 'COMPLETED')
         ->getSingleScalarResult();

        $completionScore = $totalAppts > 0 ? ($completedAppts / $totalAppts) * 100 : 0;

        // --- CRITÈRE 3: Volume de consultations complétées (15%) ---
        $consultationScore = min(($completedAppts / 50) * 100, 100);

        // --- CRITÈRE 4: Ancienneté du psychologue (15%) ---
        $psyCabinetResult = $this->em->createQuery(
            'SELECT pc FROM App\Entity\PsyCabinet pc
             JOIN pc.psychologue psy
             WHERE pc.cabinet = :cabinet
             ORDER BY psy.dateInscription ASC'
        )->setParameter('cabinet', $cabinet)
         ->setMaxResults(1)
         ->getOneOrNullResult();

        $seniorityScore = 0;
        if ($psyCabinetResult) {
            $dateInscription = $psyCabinetResult->getPsychologue()->getDateInscription();
            if ($dateInscription) {
                $diff = (new \DateTime())->diff($dateInscription);
                $monthsActive = ($diff->y * 12) + $diff->m;
                $seniorityScore = min(($monthsActive / 24) * 100, 100);
            }
        }

        // --- CRITÈRE 5: Taux de fidélisation patients (10%) ---
        $allPatientsResult = $this->em->createQuery(
            'SELECT COUNT(DISTINCT a.patient)
             FROM App\Entity\Appointment a
             JOIN a.plan pl
             JOIN pl.psychologue psy
             JOIN App\Entity\PsyCabinet pc WITH pc.psychologue = psy
             WHERE pc.cabinet = :cabinet'
        )->setParameter('cabinet', $cabinet)->getSingleScalarResult();

        $returningPatientsResult = $this->em->createQuery(
            'SELECT COUNT(DISTINCT a.patient)
             FROM App\Entity\Appointment a
             JOIN a.plan pl
             JOIN pl.psychologue psy
             JOIN App\Entity\PsyCabinet pc WITH pc.psychologue = psy
             WHERE pc.cabinet = :cabinet
             GROUP BY a.patient
             HAVING COUNT(a.id) > 1'
        )->setParameter('cabinet', $cabinet)->getResult();

        $retentionScore = $allPatientsResult > 0
            ? (count($returningPatientsResult) / $allPatientsResult) * 100 : 0;

        // --- SCORE FINAL PONDÉRÉ ---
        $finalScore = round(
            ($ratingScore       * 0.35) +
            ($completionScore   * 0.25) +
            ($consultationScore * 0.15) +
            ($seniorityScore    * 0.15) +
            ($retentionScore    * 0.10),
            2
        );

        $badge = $this->getBadge($finalScore);

        return [
            'score'   => $finalScore,
            'badge'   => $badge,
            'breakdown' => [
                'rating_score'       => round($ratingScore, 1),
                'completion_score'   => round($completionScore, 1),
                'consultation_score' => round($consultationScore, 1),
                'seniority_score'    => round($seniorityScore, 1),
                'retention_score'    => round($retentionScore, 1),
            ],
            'weights' => [
                'rating'        => '35%',
                'completion'    => '25%',
                'consultations' => '15%',
                'seniority'     => '15%',
                'retention'     => '10%',
            ],
            'avg_rating'             => round((float) $avgRating, 1),
            'total_appointments'     => (int) $totalAppts,
            'completed_appointments' => (int) $completedAppts,
        ];
    }

    public function getBadge(float $score): string
    {
        if ($score >= 76) return 'Excellence';
        if ($score >= 51) return 'Expert';
        if ($score >= 26) return 'Confirmé';
        return 'Débutant';
    }

    public function getBadgeEmoji(string $badge): string
    {
        return match ($badge) {
            'Excellence' => '💎',
            'Expert'     => '🥇',
            'Confirmé'   => '🥈',
            default      => '⭐',
        };
    }

    public function getBadgeColor(string $badge): string
    {
        return match ($badge) {
            'Excellence' => '#00BFA5',
            'Expert'     => '#F59E0B',
            'Confirmé'   => '#6B7280',
            default      => '#CD7F32',
        };
    }

    public function updateCabinetScore(Cabinet $cabinet): array
    {
        $result = $this->calculateScore($cabinet);
        $cabinet->setReputationScore($result['score']);
        $cabinet->setReputationBadge($result['badge']);
        $cabinet->setScoreUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
        return $result;
    }

    public function updateAllScores(): array
    {
        $cabinets = $this->em->getRepository(Cabinet::class)->findAll();
        $results  = [];
        foreach ($cabinets as $cabinet) {
            $score     = $this->updateCabinetScore($cabinet);
            $results[] = [
                'cabinet_id' => $cabinet->getId(),
                'ville'      => $cabinet->getVille(),
                'score'      => $score['score'],
                'badge'      => $cabinet->getReputationBadge(),
            ];
        }
        return $results;
    }
}
