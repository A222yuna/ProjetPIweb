<?php

namespace App\Service;

use App\Entity\AvailabilityException;
use App\Entity\Cabinet;
use App\Entity\Creneau;
use App\Entity\Disponibilite;
use App\Entity\SlotHistory;
use App\Entity\User;
use App\Repository\AvailabilityExceptionRepository;
use App\Repository\CreneauRepository;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SlotManagementService — Core business logic for intelligent slot management.
 *
 * Responsibilities:
 *  - checkConflicts()           : detect overlapping slots
 *  - validateAvailability()     : full validation (conflict + blocking periods + pause)
 *  - generateAlternativeSlots() : suggest free slots when requested slot is taken
 *  - handleBlockingPeriods()    : create/check absence/holiday blocks
 *  - audit()                    : write to SlotHistory
 */
class SlotManagementService
{
    /** Minimum pause between two consultations (minutes) */
    private const MIN_PAUSE_MINUTES = 10;

    /** How many alternative slots to suggest */
    private const MAX_ALTERNATIVES = 5;

    /** How many hours ahead/behind to search for alternatives */
    private const ALTERNATIVE_SEARCH_HOURS = 4;

    public function __construct(
        private EntityManagerInterface          $em,
        private CreneauRepository               $creneauRepo,
        private DisponibiliteRepository         $dispoRepo,
        private AvailabilityExceptionRepository $exceptionRepo
    ) {}

    // =========================================================================
    // 1. CONFLICT DETECTION
    // =========================================================================

    /**
     * Check if a proposed slot overlaps any existing RESERVE creneau.
     * Rule: conflict if startA < endB AND endA > startB
     *
     * @return array{conflict: bool, reason: string}
     */
    public function checkConflicts(
        Cabinet $cabinet,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeCreneauId = null
    ): array {
        $hasConflict = $this->creneauRepo->hasConflict(
            $cabinet, $date, $heureDebut, $heureFin, $excludeCreneauId
        );

        if ($hasConflict) {
            return [
                'conflict' => true,
                'reason'   => sprintf(
                    'Ce créneau (%s–%s) est déjà occupé.',
                    $heureDebut->format('H:i'),
                    $heureFin->format('H:i')
                ),
            ];
        }

        return ['conflict' => false, 'reason' => ''];
    }

    // =========================================================================
    // 2. FULL AVAILABILITY VALIDATION
    // =========================================================================

    /**
     * Full backend validation before creating or reserving a slot.
     * Checks: blocking periods → conflicts → minimum pause.
     *
     * @return array{valid: bool, errors: string[], alternatives: array}
     */
    public function validateAvailability(
        Cabinet $cabinet,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        int $dureeMinutes,
        ?int $excludeCreneauId = null
    ): array {
        $heureFin = (clone \DateTime::createFromInterface($heureDebut))
            ->modify("+{$dureeMinutes} minutes");

        $errors = [];

        // --- Check blocking periods (priority) ---
        if ($this->exceptionRepo->isBlocked($cabinet, $heureDebut, $heureFin)) {
            $errors[] = 'Ce créneau est bloqué (absence ou congé du praticien).';
        }

        // --- Check direct conflict ---
        $conflictResult = $this->checkConflicts($cabinet, $date, $heureDebut, $heureFin, $excludeCreneauId);
        if ($conflictResult['conflict']) {
            $errors[] = $conflictResult['reason'];
        }

        // --- Check minimum pause with adjacent slots ---
        if ($this->violatesMinPause($cabinet, $date, $heureDebut, $heureFin, $excludeCreneauId)) {
            $errors[] = sprintf(
                'Une pause minimale de %d minutes est requise entre deux consultations.',
                self::MIN_PAUSE_MINUTES
            );
        }

        $alternatives = [];
        if (!empty($errors)) {
            $alternatives = $this->generateAlternativeSlots($cabinet, $date, $dureeMinutes);
        }

        return [
            'valid'        => empty($errors),
            'errors'       => $errors,
            'alternatives' => $alternatives,
        ];
    }

    // =========================================================================
    // 3. MINIMUM PAUSE CHECK
    // =========================================================================

    /**
     * Returns true if the proposed slot is too close to an existing one
     * (less than MIN_PAUSE_MINUTES gap).
     */
    private function violatesMinPause(
        Cabinet $cabinet,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeCreneauId = null
    ): bool {
        $pauseSecs = self::MIN_PAUSE_MINUTES * 60;

        // Expand the window by the pause on both sides
        $windowStart = (clone \DateTime::createFromInterface($heureDebut))
            ->modify("-" . self::MIN_PAUSE_MINUTES . " minutes");
        $windowEnd = (clone \DateTime::createFromInterface($heureFin))
            ->modify("+" . self::MIN_PAUSE_MINUTES . " minutes");

        // If there's a conflict in the expanded window but NOT in the exact window,
        // it means there's a slot within the pause margin
        $conflictInWindow = $this->creneauRepo->hasConflict(
            $cabinet, $date, $windowStart, $windowEnd, $excludeCreneauId
        );
        $conflictExact = $this->creneauRepo->hasConflict(
            $cabinet, $date, $heureDebut, $heureFin, $excludeCreneauId
        );

        return $conflictInWindow && !$conflictExact;
    }

    // =========================================================================
    // 4. ALTERNATIVE SLOT GENERATION
    // =========================================================================

    /**
     * Generate up to MAX_ALTERNATIVES free slots near the requested time.
     * Searches forward and backward within ALTERNATIVE_SEARCH_HOURS.
     *
     * @return array<int, array{heure_debut: string, heure_fin: string, label: string}>
     */
    public function generateAlternativeSlots(
        Cabinet $cabinet,
        \DateTimeInterface $date,
        int $dureeMinutes
    ): array {
        // Get all disponibilites for this cabinet on this day-of-week
        $dayOfWeek = (int) $date->format('N'); // 1=Mon … 7=Sun
        $dispos    = $this->dispoRepo->findByCabinetOrdered($cabinet->getId() ?? 0);

        $occupied   = $this->creneauRepo->getOccupiedRanges($cabinet, $date);
        $blocked    = $this->getBlockedRanges($cabinet, $date);
        $allBlocked = array_merge($occupied, $blocked);

        $alternatives = [];

        foreach ($dispos as $dispo) {
            if ($dispo->getJour() !== $dayOfWeek) {
                continue;
            }

            $cursor = clone \DateTime::createFromInterface($dispo->getHeureDebut());
            $end    = clone \DateTime::createFromInterface($dispo->getHeureFin());

            while (true) {
                $slotEnd = (clone $cursor)->modify("+{$dureeMinutes} minutes");

                // Stop if slot end exceeds disponibilite end
                if ($slotEnd > $end) {
                    break;
                }

                if (!$this->isRangeOccupied($cursor, $slotEnd, $allBlocked)) {
                    $alternatives[] = [
                        'heure_debut' => $cursor->format('H:i'),
                        'heure_fin'   => $slotEnd->format('H:i'),
                        'label'       => $cursor->format('H:i') . ' – ' . $slotEnd->format('H:i'),
                    ];

                    if (count($alternatives) >= self::MAX_ALTERNATIVES) {
                        return $alternatives;
                    }
                }

                // Advance by duree + pause
                $cursor->modify("+" . ($dureeMinutes + self::MIN_PAUSE_MINUTES) . " minutes");
            }
        }

        return $alternatives;
    }

    /**
     * Check if a time range overlaps any of the given occupied ranges.
     */
    private function isRangeOccupied(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $ranges
    ): bool {
        foreach ($ranges as $range) {
            $rStart = \DateTime::createFromFormat('H:i', $range['heure_debut']);
            $rEnd   = \DateTime::createFromFormat('H:i', $range['heure_fin']);

            if ($rStart && $rEnd) {
                // Add pause buffer around occupied ranges
                $rStartWithPause = (clone $rStart)->modify('-' . self::MIN_PAUSE_MINUTES . ' minutes');
                $rEndWithPause   = (clone $rEnd)->modify('+' . self::MIN_PAUSE_MINUTES . ' minutes');

                if ($start < $rEndWithPause && $end > $rStartWithPause) {
                    return true;
                }
            }
        }
        return false;
    }

    // =========================================================================
    // 5. BLOCKING PERIODS
    // =========================================================================

    /**
     * Create a blocking period (absence, holiday, etc.) for a cabinet.
     */
    public function handleBlockingPeriods(
        Cabinet $cabinet,
        User $psychologue,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $type = AvailabilityException::TYPE_BLOCAGE,
        ?string $motif = null
    ): AvailabilityException {
        $exception = new AvailabilityException();
        $exception->setCabinet($cabinet);
        $exception->setPsychologue($psychologue);
        $exception->setDateDebut($dateDebut);
        $exception->setDateFin($dateFin);
        $exception->setType($type);
        $exception->setMotif($motif);

        $this->em->persist($exception);
        $this->em->flush();

        $this->audit(
            $psychologue,
            SlotHistory::ACTION_BLOCK,
            'AvailabilityException',
            $exception->getId(),
            null,
            [
                'type'       => $type,
                'date_debut' => $dateDebut->format('Y-m-d H:i'),
                'date_fin'   => $dateFin->format('Y-m-d H:i'),
                'motif'      => $motif,
            ]
        );

        return $exception;
    }

    /**
     * Get blocked time ranges for a cabinet on a specific date (for alternative generation).
     *
     * @return array<int, array{heure_debut: string, heure_fin: string}>
     */
    private function getBlockedRanges(Cabinet $cabinet, \DateTimeInterface $date): array
    {
        $dayStart = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
        $dayEnd   = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 23:59:59');

        $exceptions = $this->em->createQuery(
            'SELECT e FROM App\Entity\AvailabilityException e
             WHERE e.cabinet = :cabinet
               AND e.dateDebut < :dayEnd
               AND e.dateFin > :dayStart'
        )
        ->setParameter('cabinet', $cabinet)
        ->setParameter('dayStart', $dayStart)
        ->setParameter('dayEnd', $dayEnd)
        ->getResult();

        $ranges = [];
        foreach ($exceptions as $e) {
            $ranges[] = [
                'heure_debut' => $e->getDateDebut()->format('H:i'),
                'heure_fin'   => $e->getDateFin()->format('H:i'),
            ];
        }
        return $ranges;
    }

    // =========================================================================
    // 6. AUDIT LOG
    // =========================================================================

    /**
     * Write an entry to the SlotHistory audit log.
     */
    public function audit(
        ?User $user,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldState,
        ?array $newState
    ): void {
        $history = new SlotHistory();
        $history->setUser($user);
        $history->setAction($action);
        $history->setEntityType($entityType);
        $history->setEntityId($entityId);
        $history->setOldState($oldState);
        $history->setNewState($newState);

        $this->em->persist($history);
        // No flush here — caller flushes to batch with the main operation
    }

    // =========================================================================
    // 7. HELPERS
    // =========================================================================

    /**
     * Compute the end time of a slot given its start time and duration.
     */
    public function computeEndTime(\DateTimeInterface $heureDebut, int $dureeMinutes): \DateTime
    {
        return (clone \DateTime::createFromInterface($heureDebut))
            ->modify("+{$dureeMinutes} minutes");
    }

    /**
     * Snapshot a Disponibilite for audit purposes.
     */
    public function snapshotDispo(Disponibilite $dispo): array
    {
        return [
            'id'               => $dispo->getId(),
            'cabinet_id'       => $dispo->getCabinet()?->getId(),
            'jour'             => $dispo->getJour(),
            'heure_debut'      => $dispo->getHeureDebut()?->format('H:i'),
            'heure_fin'        => $dispo->getHeureFin()?->format('H:i'),
            'duree_consultation' => $dispo->getDureeConsultation(),
        ];
    }

    /**
     * Snapshot a Creneau for audit purposes.
     */
    public function snapshotCreneau(Creneau $creneau): array
    {
        return [
            'id'             => $creneau->getId(),
            'disponibilite'  => $creneau->getDisponibilite()?->getId(),
            'patient'        => $creneau->getPatient()?->getId(),
            'date_creneau'   => $creneau->getDateCreneau()?->format('Y-m-d'),
            'heure'          => $creneau->getHeure()?->format('H:i'),
            'statut'         => $creneau->getStatut(),
        ];
    }
}
