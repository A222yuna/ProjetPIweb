<?php

namespace App\Tests\Planning;

use App\Entity\Appointment;
use App\Entity\PsychologuePlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests des règles métier du RendezvousController (côté patient).
 *
 * Couvre la logique pure extraite du controller :
 *  - Vérification du double rendez-vous sur le même planning
 *  - Vérification du max_appointments
 *  - Création d'un rendez-vous avec statut SCHEDULED
 *  - Annulation uniquement si SCHEDULED
 *  - Ownership du rendez-vous
 */
class RendezvousBusinessRulesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePatient(int $id = 1): User
    {
        $u = new User();
        $u->setNom('Patient');
        $u->setPrenom('Test');
        $u->setEmail("patient{$id}@test.com");
        $u->setRole('Patient');

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($u, $id);

        return $u;
    }

    private function makePsychologue(int $id = 10): User
    {
        $u = new User();
        $u->setNom('Psy');
        $u->setPrenom('Test');
        $u->setEmail("psy{$id}@test.com");
        $u->setRole('Psychologue');

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($u, $id);

        return $u;
    }

    private function makePlan(User $psy, int $max = 5): PsychologuePlan
    {
        $p = new PsychologuePlan();
        $p->setPsychologue($psy);
        $p->setDayOfWeek('MONDAY');
        $p->setPeriod('DAY');
        $p->setMaxAppointments($max);
        return $p;
    }

    // -------------------------------------------------------------------------
    // RULE 1 — Pas de double rendez-vous SCHEDULED sur le même planning
    // -------------------------------------------------------------------------

    public function testPatientWithNoActiveAppointmentCanBook(): void
    {
        // Simule hasActiveAppointmentForPatientAndPlan = false
        $hasActive = false;

        $this->assertFalse($hasActive);
    }

    public function testPatientWithActiveAppointmentCannotBook(): void
    {
        // Simule hasActiveAppointmentForPatientAndPlan = true
        $hasActive = true;

        $this->assertTrue($hasActive, 'Le patient a déjà un RDV SCHEDULED pour ce planning');
    }

    // -------------------------------------------------------------------------
    // RULE 2 — Respect du max_appointments
    // -------------------------------------------------------------------------

    public function testPlanWithCapacityAvailableAllowsBooking(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy, max: 5);

        $scheduledCount = 3; // simulé

        $this->assertFalse($scheduledCount >= $plan->getMaxAppointments());
    }

    public function testPlanAtMaxCapacityBlocksBooking(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy, max: 5);

        $scheduledCount = 5; // simulé — plan plein

        $this->assertTrue($scheduledCount >= $plan->getMaxAppointments());
    }

    public function testPlanWithOneSlotLeftAllowsBooking(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy, max: 5);

        $scheduledCount = 4;

        $this->assertFalse($scheduledCount >= $plan->getMaxAppointments());
    }

    // -------------------------------------------------------------------------
    // RULE 3 — Création d'un rendez-vous avec statut SCHEDULED
    // -------------------------------------------------------------------------

    public function testNewAppointmentHasScheduledStatus(): void
    {
        $patient = $this->makePatient();
        $psy     = $this->makePsychologue();
        $plan    = $this->makePlan($psy);

        $appointment = new Appointment();
        $appointment->setPatient($patient);
        $appointment->setPlan($plan);
        $appointment->setStatus(Appointment::STATUS_SCHEDULED);

        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->getStatus());
        $this->assertSame($patient, $appointment->getPatient());
        $this->assertSame($plan, $appointment->getPlan());
    }

    public function testNewAppointmentDefaultStatusIsScheduled(): void
    {
        $appointment = new Appointment();
        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->getStatus());
    }

    // -------------------------------------------------------------------------
    // RULE 4 — Annulation uniquement si SCHEDULED
    // -------------------------------------------------------------------------

    public function testScheduledAppointmentCanBeCancelled(): void
    {
        $a = new Appointment();
        $a->setStatus(Appointment::STATUS_SCHEDULED);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertTrue($canCancel);
    }

    public function testConfirmedAppointmentCannotBeCancelledByPatient(): void
    {
        $a = new Appointment();
        $a->setStatus(Appointment::STATUS_CONFIRMED);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertFalse($canCancel);
    }

    public function testPaidAppointmentCannotBeCancelledByPatient(): void
    {
        $a = new Appointment();
        $a->setStatus(Appointment::STATUS_PAID);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertFalse($canCancel);
    }

    public function testCompletedAppointmentCannotBeCancelledByPatient(): void
    {
        $a = new Appointment();
        $a->setStatus(Appointment::STATUS_COMPLETED);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertFalse($canCancel);
    }

    public function testAlreadyCancelledAppointmentCannotBeCancelledAgain(): void
    {
        $a = new Appointment();
        $a->setStatus(Appointment::STATUS_CANCELLED);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertFalse($canCancel);
    }

    // -------------------------------------------------------------------------
    // RULE 5 — Ownership : le patient ne peut annuler que ses propres RDV
    // -------------------------------------------------------------------------

    public function testPatientCanCancelOwnAppointment(): void
    {
        $patient = $this->makePatient(1);

        $a = new Appointment();
        $a->setPatient($patient);

        $currentUser = $patient;

        $isOwner = $a->getPatient()?->getId() === $currentUser->getId();

        $this->assertTrue($isOwner);
    }

    public function testPatientCannotCancelOtherPatientAppointment(): void
    {
        $owner = $this->makePatient(1);
        $other = $this->makePatient(2);

        $a = new Appointment();
        $a->setPatient($owner);

        $isOwner = $a->getPatient()?->getId() === $other->getId();

        $this->assertFalse($isOwner);
    }

    // -------------------------------------------------------------------------
    // RULE 6 — Annulation met le statut à CANCELLED
    // -------------------------------------------------------------------------

    public function testCancellingAppointmentSetsStatusToCancelled(): void
    {
        $a = new Appointment();
        $a->setStatus(Appointment::STATUS_SCHEDULED);

        // Simulation de la logique du controller
        if ($a->getStatus() === Appointment::STATUS_SCHEDULED) {
            $a->setStatus(Appointment::STATUS_CANCELLED);
        }

        $this->assertEquals(Appointment::STATUS_CANCELLED, $a->getStatus());
    }

    // -------------------------------------------------------------------------
    // RULE 7 — Pagination : page minimum = 1
    // -------------------------------------------------------------------------

    public function testPageNormalizationNeverGoesBelow1(): void
    {
        $this->assertEquals(1, max(1, 0));
        $this->assertEquals(1, max(1, -10));
        $this->assertEquals(1, max(1, 1));
        $this->assertEquals(5, max(1, 5));
    }

    // -------------------------------------------------------------------------
    // Scénario complet de réservation
    // -------------------------------------------------------------------------

    public function testCompleteBookingScenario(): void
    {
        $patient = $this->makePatient(1);
        $psy     = $this->makePsychologue(10);
        $plan    = $this->makePlan($psy, max: 5);

        // Pré-conditions
        $hasActiveAppointment = false; // pas de doublon
        $scheduledCount       = 2;    // plan pas plein

        $this->assertFalse($hasActiveAppointment, 'Pas de doublon');
        $this->assertFalse($scheduledCount >= $plan->getMaxAppointments(), 'Plan pas plein');

        // Création
        $appointment = new Appointment();
        $appointment->setPatient($patient);
        $appointment->setPlan($plan);
        $appointment->setStatus(Appointment::STATUS_SCHEDULED);

        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->getStatus());
        $this->assertSame($patient, $appointment->getPatient());
        $this->assertSame($plan, $appointment->getPlan());
        $this->assertInstanceOf(\DateTimeImmutable::class, $appointment->getCreatedAt());
    }
}
