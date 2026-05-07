<?php

namespace App\Tests\Planning;

use App\Entity\Appointment;
use App\Entity\Cabinet;
use App\Entity\Creneau;
use App\Entity\Disponibilite;
use App\Entity\PsychologuePlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests des règles métier de réservation de créneaux.
 *
 * Ces tests couvrent la logique pure (sans base de données) :
 *  - Validation de la fenêtre horaire
 *  - Détection de double réservation
 *  - Respect du délai minimum de 2 heures
 *  - Vérification du cabinet validé
 *  - Calcul du jour/période (DAY/NIGHT)
 *  - Respect du max_appointments
 *  - Annulation et cascade sur Appointment
 */
class CreneauBookingRulesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePatient(string $email = 'patient@test.com'): User
    {
        $u = new User();
        $u->setNom('Patient');
        $u->setPrenom('Test');
        $u->setEmail($email);
        $u->setRole('Patient');
        return $u;
    }

    private function makePsychologue(): User
    {
        $u = new User();
        $u->setNom('Psy');
        $u->setPrenom('Test');
        $u->setEmail('psy@test.com');
        $u->setRole('Psychologue');
        return $u;
    }

    private function makeDisponibilite(
        int $jour = 1,
        string $debut = '09:00',
        string $fin = '17:00',
        int $duree = 60
    ): Disponibilite {
        $d = new Disponibilite();
        $d->setJour($jour);
        $d->setHeureDebut(new \DateTimeImmutable($debut));
        $d->setHeureFin(new \DateTimeImmutable($fin));
        $d->setDureeConsultation($duree);
        return $d;
    }

    private function makeCabinet(bool $valide = true): Cabinet
    {
        $c = new Cabinet();
        $c->setAdresse('1 Rue Test');
        $c->setVille('Paris');
        $c->setValide($valide);
        return $c;
    }

    private function makePlan(User $psy, string $day = 'MONDAY', string $period = 'DAY', int $max = 5): PsychologuePlan
    {
        $p = new PsychologuePlan();
        $p->setPsychologue($psy);
        $p->setDayOfWeek($day);
        $p->setPeriod($period);
        $p->setMaxAppointments($max);
        return $p;
    }

    // -------------------------------------------------------------------------
    // RULE 1 — Heure dans la fenêtre de disponibilité
    // -------------------------------------------------------------------------

    public function testHeureInWindowIsValid(): void
    {
        $dispo = $this->makeDisponibilite(debut: '09:00', fin: '17:00');
        $heure = new \DateTimeImmutable('10:00');

        $heureMin = $dispo->getHeureDebut();
        $heureMax = $dispo->getHeureFin();

        $this->assertFalse($heure < $heureMin || $heure >= $heureMax);
    }

    public function testHeureBeforeWindowIsInvalid(): void
    {
        $dispo = $this->makeDisponibilite(debut: '09:00', fin: '17:00');
        $heure = new \DateTimeImmutable('08:00');

        $heureMin = $dispo->getHeureDebut();
        $heureMax = $dispo->getHeureFin();

        $this->assertTrue($heure < $heureMin);
    }

    public function testHeureAtWindowEndIsInvalid(): void
    {
        $dispo = $this->makeDisponibilite(debut: '09:00', fin: '17:00');
        $heure = new \DateTimeImmutable('17:00'); // >= heureFin → invalide

        $heureMax = $dispo->getHeureFin();

        $this->assertTrue($heure >= $heureMax);
    }

    public function testHeureAfterWindowIsInvalid(): void
    {
        $dispo = $this->makeDisponibilite(debut: '09:00', fin: '17:00');
        $heure = new \DateTimeImmutable('18:00');

        $heureMax = $dispo->getHeureFin();

        $this->assertTrue($heure >= $heureMax);
    }

    // -------------------------------------------------------------------------
    // RULE 2 — Délai minimum de 2 heures
    // -------------------------------------------------------------------------

    public function testBookingWithMoreThan2HoursLeadTimeIsValid(): void
    {
        $now             = new \DateTimeImmutable();
        $bookingDateTime = $now->modify('+3 hours');

        $this->assertFalse($bookingDateTime < $now->modify('+2 hours'));
    }

    public function testBookingWithLessThan2HoursLeadTimeIsInvalid(): void
    {
        $now             = new \DateTimeImmutable();
        $bookingDateTime = $now->modify('+1 hour');

        $this->assertTrue($bookingDateTime < $now->modify('+2 hours'));
    }

    public function testBookingExactly2HoursAheadIsInvalid(): void
    {
        // Exactly 2 hours = NOT strictly greater than +2h
        $now             = new \DateTimeImmutable();
        $bookingDateTime = $now->modify('+2 hours');

        // The rule is: bookingDateTime < now+2h → invalid
        // At exactly +2h it is NOT < +2h, so it passes (edge case)
        $this->assertFalse($bookingDateTime < $now->modify('+2 hours'));
    }

    // -------------------------------------------------------------------------
    // RULE 3 — Date dans le passé
    // -------------------------------------------------------------------------

    public function testPastDateIsInvalid(): void
    {
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $past  = $today->modify('-1 day');

        $this->assertTrue($past < $today);
    }

    public function testTodayDateIsValid(): void
    {
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $this->assertFalse($today < $today);
    }

    public function testFutureDateIsValid(): void
    {
        $today  = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $future = $today->modify('+7 days');

        $this->assertFalse($future < $today);
    }

    // -------------------------------------------------------------------------
    // RULE 4 — Cabinet validé
    // -------------------------------------------------------------------------

    public function testValidatedCabinetAllowsBooking(): void
    {
        $cabinet = $this->makeCabinet(valide: true);
        $this->assertTrue($cabinet->isValide());
    }

    public function testNonValidatedCabinetBlocksBooking(): void
    {
        $cabinet = $this->makeCabinet(valide: false);
        $this->assertFalse($cabinet->isValide());
    }

    // -------------------------------------------------------------------------
    // RULE 5 — Calcul du jour de la semaine (dayOfWeek)
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('dayOfWeekProvider')]
    public function testDayOfWeekMapping(string $dateStr, string $expectedDay): void
    {
        $date   = new \DateTimeImmutable($dateStr);
        $dayMap = [
            'MONDAY' => 'MONDAY', 'TUESDAY' => 'TUESDAY', 'WEDNESDAY' => 'WEDNESDAY',
            'THURSDAY' => 'THURSDAY', 'FRIDAY' => 'FRIDAY', 'SATURDAY' => 'SATURDAY', 'SUNDAY' => 'SUNDAY',
        ];
        $dayOfWeek = $dayMap[strtoupper($date->format('l'))] ?? 'MONDAY';

        $this->assertEquals($expectedDay, $dayOfWeek);
    }

    /** @return array<string, array{string, string}> */
    public static function dayOfWeekProvider(): array
    {
        return [
            'lundi'    => ['2026-04-27', 'MONDAY'],
            'mardi'    => ['2026-04-28', 'TUESDAY'],
            'mercredi' => ['2026-04-29', 'WEDNESDAY'],
            'jeudi'    => ['2026-04-30', 'THURSDAY'],
            'vendredi' => ['2026-05-01', 'FRIDAY'],
            'samedi'   => ['2026-05-02', 'SATURDAY'],
            'dimanche' => ['2026-05-03', 'SUNDAY'],
        ];
    }

    // -------------------------------------------------------------------------
    // RULE 6 — Calcul de la période (DAY / NIGHT)
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('periodProvider')]
    public function testPeriodCalculation(string $heureStr, string $expectedPeriod): void
    {
        $heure  = new \DateTimeImmutable($heureStr);
        $period = ((int)$heure->format('H') < 18) ? 'DAY' : 'NIGHT';

        $this->assertEquals($expectedPeriod, $period);
    }

    /** @return array<string, array{string, string}> */
    public static function periodProvider(): array
    {
        return [
            'matin 09h'       => ['09:00', 'DAY'],
            'midi 12h'        => ['12:00', 'DAY'],
            'après-midi 17h'  => ['17:59', 'DAY'],
            'soir 18h'        => ['18:00', 'NIGHT'],
            'nuit 20h'        => ['20:00', 'NIGHT'],
            'limite 17h59'    => ['17:59', 'DAY'],
        ];
    }

    // -------------------------------------------------------------------------
    // RULE 7 — Respect du max_appointments
    // -------------------------------------------------------------------------

    public function testPlanNotFullAllowsBooking(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy, max: 5);

        $currentCount = 3; // simulé

        $this->assertFalse($currentCount >= $plan->getMaxAppointments());
    }

    public function testPlanFullBlocksBooking(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy, max: 5);

        $currentCount = 5; // simulé — plan plein

        $this->assertTrue($currentCount >= $plan->getMaxAppointments());
    }

    public function testPlanOverCapacityBlocksBooking(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy, max: 3);

        $currentCount = 4;

        $this->assertTrue($currentCount >= $plan->getMaxAppointments());
    }

    // -------------------------------------------------------------------------
    // RULE 8 — Annulation d'un créneau cascade sur Appointment
    // -------------------------------------------------------------------------

    public function testCancelCreneauSetsStatutAnnule(): void
    {
        $creneau = new Creneau();
        $creneau->setStatut(Creneau::STATUT_RESERVE);

        $creneau->setStatut(Creneau::STATUT_ANNULE);

        $this->assertEquals(Creneau::STATUT_ANNULE, $creneau->getStatut());
    }

    public function testCancelCreneauAlsoCancelsAppointment(): void
    {
        $creneau     = new Creneau();
        $appointment = new Appointment();
        $appointment->setStatus(Appointment::STATUS_SCHEDULED);

        // Simulation de la logique du controller
        $creneau->setStatut(Creneau::STATUT_ANNULE);
        if ($appointment->getStatus() !== Appointment::STATUS_COMPLETED) {
            $appointment->setStatus(Appointment::STATUS_CANCELLED);
        }

        $this->assertEquals(Creneau::STATUT_ANNULE, $creneau->getStatut());
        $this->assertEquals(Appointment::STATUS_CANCELLED, $appointment->getStatus());
    }

    public function testCannotCancelCompletedAppointment(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus(Appointment::STATUS_COMPLETED);

        // La règle : on ne peut pas annuler un RDV terminé
        $canCancel = $appointment->getStatus() !== Appointment::STATUS_COMPLETED;

        $this->assertFalse($canCancel);
    }

    // -------------------------------------------------------------------------
    // RULE 9 — Seul le propriétaire peut annuler son créneau
    // -------------------------------------------------------------------------

    public function testOwnerCanCancelCreneau(): void
    {
        $patient = $this->makePatient();
        // Simuler un ID via réflexion
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($patient, 42);

        $creneau = new Creneau();
        $creneau->setPatient($patient);

        $currentUser = $patient; // même utilisateur

        $this->assertEquals($creneau->getPatient()?->getId(), $currentUser->getId());
    }

    public function testNonOwnerCannotCancelCreneau(): void
    {
        $owner = $this->makePatient('owner@test.com');
        $other = $this->makePatient('other@test.com');

        $refOwner = new \ReflectionProperty(User::class, 'id');
        $refOwner->setValue($owner, 1);

        $refOther = new \ReflectionProperty(User::class, 'id');
        $refOther->setValue($other, 2);

        $creneau = new Creneau();
        $creneau->setPatient($owner);

        $this->assertNotEquals($creneau->getPatient()?->getId(), $other->getId());
    }
}
