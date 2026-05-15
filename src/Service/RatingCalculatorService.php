<?php

namespace App\Service;

use App\Entity\Cabinet;
use App\Entity\Rating;
use Doctrine\ORM\EntityManagerInterface;

class RatingCalculatorService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function calculateGlobalNote(Rating $rating): float
    {
        $ecoute        = $rating->getNoteEcoute()        ?? 0;
        $competence    = $rating->getNoteCompetence()    ?? 0;
        $ponctualite   = $rating->getNotePonctualite()   ?? 0;
        $environnement = $rating->getNoteEnvironnement() ?? 0;

        return round(
            ($ecoute * 0.30) + ($competence * 0.35) +
            ($ponctualite * 0.20) + ($environnement * 0.15),
            1
        );
    }

    public function getCabinetRatingStats(Cabinet $cabinet): array
    {
        $ratings = $this->em->createQuery(
            'SELECT r FROM App\Entity\Rating r
             WHERE r.cabinet = :cabinet
             ORDER BY r.createdAt DESC'
        )->setParameter('cabinet', $cabinet)->getResult();

        $empty = [
            'moyenne_globale'       => 0,
            'moyenne_ecoute'        => 0,
            'moyenne_competence'    => 0,
            'moyenne_ponctualite'   => 0,
            'moyenne_environnement' => 0,
            'total_avis'            => 0,
            'distribution'          => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'pourcentage_positif'   => 0,
        ];

        if (empty($ratings)) {
            return $empty;
        }

        $total = count($ratings);
        $sums  = ['global' => 0, 'ecoute' => 0, 'competence' => 0, 'ponctualite' => 0, 'environnement' => 0];
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        foreach ($ratings as $r) {
            $global = $r->getNoteGlobale() ?? $this->calculateGlobalNote($r);
            $sums['global']        += $global;
            $sums['ecoute']        += $r->getNoteEcoute()        ?? 0;
            $sums['competence']    += $r->getNoteCompetence()    ?? 0;
            $sums['ponctualite']   += $r->getNotePonctualite()   ?? 0;
            $sums['environnement'] += $r->getNoteEnvironnement() ?? 0;
            $rounded = max(1, min(5, (int) round($global)));
            $distribution[$rounded]++;
        }

        $moyenneGlobale = round($sums['global'] / $total, 1);
        $positif        = $distribution[4] + $distribution[5];

        return [
            'moyenne_globale'       => $moyenneGlobale,
            'moyenne_ecoute'        => round($sums['ecoute'] / $total, 1),
            'moyenne_competence'    => round($sums['competence'] / $total, 1),
            'moyenne_ponctualite'   => round($sums['ponctualite'] / $total, 1),
            'moyenne_environnement' => round($sums['environnement'] / $total, 1),
            'total_avis'            => $total,
            'distribution'          => $distribution,
            'pourcentage_positif'   => round(($positif / $total) * 100, 1),
        ];
    }

    public function prepareRating(Rating $rating): Rating
    {
        $rating->setNoteGlobale($this->calculateGlobalNote($rating));
        if (!$rating->getCreatedAt()) {
            $rating->setCreatedAt(new \DateTimeImmutable());
        }
        return $rating;
    }
}
