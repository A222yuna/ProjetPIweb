<?php

namespace App\Service;

use App\Entity\Cabinet;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * ReputationCalculatorService — Advanced business logic for cabinet reputation.
 *
 * Score breakdown (normalized 0–100):
 *   - Moyenne des avis patients     → 40%
 *   - Taux de ponctualité           → 20%
 *   - Volume consultations          → 15%
 *   - Ancienneté du praticien       → 10%
 *   - Taux de fidélisation          → 15%
 *
 * Badge thresholds:
 *   ⭐ Débutant   : score < 40
 *   🥈 Confirmé  : 40 ≤ score < 60
 *   🥇 Expert    : 60 ≤ score < 80
 *   💎 Excellence: score ≥ 80
 */
class ReputationCalculatorService
{
    /** Ponctualité threshold in minutes: a creneau is "on time" if within this margin */
    private const PUNCTUALITY_THRESHOLD_MINUTES = 10;

    /** Volume cap: 50 completed creneaux = max volume score */
    private const VOLUME_CAP = 50;

    /** Seniority cap: 24 months = max seniority score */
    private const SENIORITY_CAP_MONTHS = 24;

    public function __construct(
        private EntityManagerInterface $em,
        private RatingRepository $ratingRepository
    ) {}

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Calculate the full reputation score for a cabinet.
     * Returns a structured array with score, badge, and per-indicator breakdown.
     */
    public function calculate(Cabinet $cabinet): array
    {
        $ratingScore      = $this->computeRatingScore($cabinet);
        $punctualityScore = $this->computePunctualityScore($cabinet);
        $volumeScore      = $this->computeVolumeScore($cabinet);
        $seniorityScore   = $this->computeSeniorityScore($cabinet);
        $retentionScore   = $this->computeRetentionScore($cabinet);

        $finalScore = round(
            ($ratingScore      * 0.40) +
            ($punctualityScore * 0.20) +
            ($volumeScore      * 0.15) +
            ($seniorityScore   * 0.10) +
            ($retentionScore   * 0.15),
            2
        );

        $badge = $this->resolveBadge($finalScore);

        return [
            'score'   => $finalScore,
            'badge'   => $badge,
            'emoji'   => $this->badgeEmoji($badge),
            'color'   => $this->badgeColor($badge),
            'breakdown' => [
                'rating_score'      => round($ratingScore, 1),
                'punctuality_score' => round($punctualityScore, 1),
                'volume_score'      => round($volumeScore, 1),
                'seniority_score'   => round($seniorityScore, 1),
                'retention_score'   => round($retentionScore, 1),
            ],
            'weights' => [
                'rating'      => '40%',
                'punctuality' => '20%',
                'volume'      => '15%',
                'seniority'   => '10%',
                'retention'   => '15%',
            ],
            'meta' => [
                'avg_rating'       => round($this->ratingRepository->getWeightedAverage($cabinet), 1),
                'total_patients'   => $this->ratingRepository->countTotalPatients($cabinet),
                'loyal_patients'   => $this->ratingRepository->countLoyalPatients($cabinet),
                'total_creneaux'   => $this->countTotalCreneaux($cabinet),
                'reserved_creneaux'=> $this->countReservedCreneaux($cabinet),
            ],
        ];
    }

    /**
     * Persist the calculated score and badge to the cabinet entity.
     */
    public function updateAndPersist(Cabinet $cabinet): array
    {
        $result = $this->calculate($cabinet);
        $cabinet->setReputationScore($result['score']);
        $cabinet->setReputationBadge($result['badge']);
        $cabinet->setScoreUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
        return $result;
    }

    /**
     * Recalculate and persist scores for all cabinets.
     *
     * @return array<int, array{cabinet_id: int, ville: string, score: float, badge: string}>
     */
    public function updateAll(): array
    {
        $cabinets = $this->em->getRepository(Cabinet::class)->findAll();
        $results  = [];
        foreach ($cabinets as $cabinet) {
            $r         = $this->updateAndPersist($cabinet);
            $results[] = [
                'cabinet_id' => $cabinet->getId(),
                'ville'      => $cabinet->getVille(),
                'score'      => $r['score'],
                'badge'      => $r['badge'],
            ];
        }
        return $results;
    }

    // =========================================================================
    // BADGE HELPERS
    // =========================================================================

    public function resolveBadge(float $score): string
    {
        if ($score >= 80) return 'Excellence';
        if ($score >= 60) return 'Expert';
        if ($score >= 40) return 'Confirmé';
        return 'Débutant';
    }

    public function badgeEmoji(string $badge): string
    {
        return match ($badge) {
            'Excellence' => '💎',
            'Expert'     => '🥇',
            'Confirmé'   => '🥈',
            default      => '⭐',
        };
    }

    public function badgeColor(string $badge): string
    {
        return match ($badge) {
            'Excellence' => '#00BFA5',
            'Expert'     => '#F59E0B',
            'Confirmé'   => '#6B7280',
            default      => '#CD7F32',
        };
    }

    // =========================================================================
    // INDICATOR 1 — Moyenne des avis patients (40%)
    // =========================================================================

    /**
     * Converts the weighted average rating (1–5) to a 0–100 score.
     * Uses noteGlobale when available, falls back to note.
     */
    private function computeRatingScore(Cabinet $cabinet): float
    {
        $avg = $this->ratingRepository->getWeightedAverage($cabinet);
        return $avg > 0 ? ($avg / 5.0) * 100 : 0.0;
    }

    // =========================================================================
    // INDICATOR 2 — Taux de ponctualité (20%)
    // =========================================================================

    /**
     * A creneau is "on time" if its heure is within PUNCTUALITY_THRESHOLD_MINUTES
     * of the disponibilite.heureDebut.
     *
     * Since we don't store actual arrival time, we use the creneau.heure vs
     * disponibilite.heureDebut as a proxy: if heure <= heureDebut + threshold → ponctuel.
     */
    private function computePunctualityScore(Cabinet $cabinet): float
    {
        $conn = $this->em->getConnection();

        $row = $conn->executeQuery(
            'SELECT
                COUNT(*) AS total,
                SUM(
                    CASE WHEN TIME_TO_SEC(c.heure) <= TIME_TO_SEC(d.heure_debut) + :threshold
                    THEN 1 ELSE 0 END
                ) AS ponctuel
             FROM creneau c
             JOIN disponibilite d ON c.disponibilite_id = d.id
             WHERE d.cabinet_id = :cabinet_id
               AND c.statut = :statut',
            [
                'cabinet_id' => $cabinet->getId(),
                'statut'     => 'RESERVE',
                'threshold'  => self::PUNCTUALITY_THRESHOLD_MINUTES * 60,
            ]
        )->fetchAssociative();

        $total    = (int) ($row['total']    ?? 0);
        $ponctuel = (int) ($row['ponctuel'] ?? 0);

        return $total > 0 ? ($ponctuel / $total) * 100 : 0.0;
    }

    // =========================================================================
    // INDICATOR 3 — Volume de consultations (15%)
    // =========================================================================

    /**
     * Number of reserved creneaux, capped at VOLUME_CAP for normalization.
     */
    private function computeVolumeScore(Cabinet $cabinet): float
    {
        $reserved = $this->countReservedCreneaux($cabinet);
        return min(($reserved / self::VOLUME_CAP) * 100, 100.0);
    }

    // =========================================================================
    // INDICATOR 4 — Ancienneté du praticien (10%)
    // =========================================================================

    /**
     * Uses the earliest psychologist's dateInscription linked to this cabinet.
     * Capped at SENIORITY_CAP_MONTHS months.
     */
    private function computeSeniorityScore(Cabinet $cabinet): float
    {
        $psyCabinet = $this->em->createQuery(
            'SELECT pc FROM App\Entity\PsyCabinet pc
             JOIN pc.psychologue psy
             WHERE pc.cabinet = :cabinet
             ORDER BY psy.dateInscription ASC'
        )
        ->setParameter('cabinet', $cabinet)
        ->setMaxResults(1)
        ->getOneOrNullResult();

        if (!$psyCabinet) {
            return 0.0;
        }

        $dateInscription = $psyCabinet->getPsychologue()->getDateInscription();
        if (!$dateInscription) {
            return 0.0;
        }

        $diff         = (new \DateTime())->diff($dateInscription);
        $monthsActive = ($diff->y * 12) + $diff->m;

        return min(($monthsActive / self::SENIORITY_CAP_MONTHS) * 100, 100.0);
    }

    // =========================================================================
    // INDICATOR 5 — Taux de fidélisation (15%)
    // =========================================================================

    /**
     * Loyal patient = patient with >= 2 creneaux at this cabinet.
     * Rate = loyal / total * 100.
     */
    private function computeRetentionScore(Cabinet $cabinet): float
    {
        $total  = $this->ratingRepository->countTotalPatients($cabinet);
        $loyal  = $this->ratingRepository->countLoyalPatients($cabinet);

        return $total > 0 ? ($loyal / $total) * 100 : 0.0;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function countTotalCreneaux(Cabinet $cabinet): int
    {
        return (int) $this->em->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :cabinet'
        )->setParameter('cabinet', $cabinet)->getSingleScalarResult();
    }

    private function countReservedCreneaux(Cabinet $cabinet): int
    {
        return (int) $this->em->createQuery(
            'SELECT COUNT(c.id)
             FROM App\Entity\Creneau c
             JOIN c.disponibilite d
             WHERE d.cabinet = :cabinet AND c.statut = :statut'
        )
        ->setParameter('cabinet', $cabinet)
        ->setParameter('statut', 'RESERVE')
        ->getSingleScalarResult();
    }
}
