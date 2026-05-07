<?php

namespace App\Tests\Planning;

use App\Entity\Appointment;
use App\Entity\PsychologuePlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests des transitions de statut des rendez-vous (Appointment).
 *
 * Couvre :
 *  - Statut par défaut
 *  - Toutes les transitions valides
 *  - Règle : seul SCHEDULED peut être modifié par le psychologue
 *  - Règle : seul SCHEDULED peut être annulé par le patient
 *  - Constantes de statut
 *  - Création automatique de createdAt
 */
class AppointmentStatusTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAppointment(?string $status = null): Appointment
    {
        $a = new Appointment();
        if ($status !== null) {
            $a->setStatus($status);
        }
        return $a;
    }

    private function makePatient(): User
    {
        $u = new User();
        $u->setNom('Patient');
        $u->setPrenom('Test');
        $u->setEmail('patient@test.com');
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

    private function makePlan(User $psy): PsychologuePlan
    {
        $p = new PsychologuePlan();
        $p->setPsychologue($psy);
        $p->setDayOfWeek('MONDAY');
        $p->setPeriod('DAY');
        $p->setMaxAppointments(5);
        return $p;
    }

    // -------------------------------------------------------------------------
    // Statut par défaut
    // -------------------------------------------------------------------------

    public function testDefaultStatusIsScheduled(): void
    {
        $appointment = new Appointment();
        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->getStatus());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $appointment = new Appointment();
        $this->assertInstanceOf(\DateTimeImmutable::class, $appointment->getCreatedAt());
    }

    public function testCreatedAtIsRecentOnConstruction(): void
    {
        $before      = new \DateTimeImmutable();
        $appointment = new Appointment();
        $after       = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $appointment->getCreatedAt());
        $this->assertLessThanOrEqual($after, $appointment->getCreatedAt());
    }

    // -------------------------------------------------------------------------
    // Constantes de statut
    // -------------------------------------------------------------------------

    public function testStatusConstants(): void
    {
        $this->assertEquals('SCHEDULED', Appointment::STATUS_SCHEDULED);
        $this->assertEquals('CONFIRMED', Appointment::STATUS_CONFIRMED);
        $this->assertEquals('PAID', Appointment::STATUS_PAID);
        $this->assertEquals('CANCELLED', Appointment::STATUS_CANCELLED);
        $this->assertEquals('COMPLETED', Appointment::STATUS_COMPLETED);
    }

    // -------------------------------------------------------------------------
    // Transitions de statut
    // -------------------------------------------------------------------------

    public function testScheduledToConfirmed(): void
    {
        $a = $this->makeAppointment(Appointment::STATUS_SCHEDULED);
        $a->setStatus(Appointment::STATUS_CONFIRMED);

        $this->assertEquals(Appointment::STATUS_CONFIRMED, $a->getStatus());
    }

    public function testScheduledToCompleted(): void
    {
        $a = $this->makeAppointment(Appointment::STATUS_SCHEDULED);
        $a->setStatus(Appointment::STATUS_COMPLETED);

        $this->assertEquals(Appointment::STATUS_COMPLETED, $a->getStatus());
    }

    public function testScheduledToCancelled(): void
    {
        $a = $this->makeAppointment(Appointment::STATUS_SCHEDULED);
        $a->setStatus(Appointment::STATUS_CANCELLED);

        $this->assertEquals(Appointment::STATUS_CANCELLED, $a->getStatus());
    }

    public function testConfirmedToPaid(): void
    {
        $a = $this->makeAppointment(Appointment::STATUS_CONFIRMED);
        $a->setStatus(Appointment::STATUS_PAID);

        $this->assertEquals(Appointment::STATUS_PAID, $a->getStatus());
    }

    // -------------------------------------------------------------------------
    // Règle : seul SCHEDULED peut être modifié par le psychologue
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('nonScheduledStatusProvider')]
    public function testPsychologueCannotModifyNonScheduledAppointment(string $status): void
    {
        $a = $this->makeAppointment($status);

        // Règle du controller : seul SCHEDULED peut être modifié
        $canModify = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertFalse($canModify, "Un RDV en statut '$status' ne devrait pas être modifiable");
    }

    /** @return array<string, array{string}> */
    public static function nonScheduledStatusProvider(): array
    {
        return [
            'confirmed'  => [Appointment::STATUS_CONFIRMED],
            'paid'       => [Appointment::STATUS_PAID],
            'cancelled'  => [Appointment::STATUS_CANCELLED],
            'completed'  => [Appointment::STATUS_COMPLETED],
        ];
    }

    public function testPsychologueCanModifyScheduledAppointment(): void
    {
        $a = $this->makeAppointment(Appointment::STATUS_SCHEDULED);

        $canModify = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertTrue($canModify);
    }

    // -------------------------------------------------------------------------
    // Règle : seul SCHEDULED peut être annulé par le patient
    // -------------------------------------------------------------------------

    public function testPatientCanCancelScheduledAppointment(): void
    {
        $a = $this->makeAppointment(Appointment::STATUS_SCHEDULED);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertTrue($canCancel);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonScheduledStatusProvider')]
    public function testPatientCannotCancelNonScheduledAppointment(string $status): void
    {
        $a = $this->makeAppointment($status);

        $canCancel = $a->getStatus() === Appointment::STATUS_SCHEDULED;

        $this->assertFalse($canCancel);
    }

    // -------------------------------------------------------------------------
    // Ownership : le psychologue ne peut modifier que ses propres RDV
    // -------------------------------------------------------------------------

    public function testPsychologueOwnsAppointmentViaplan(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy);

        // Simuler un ID
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($psy, 10);

        $a = new Appointment();
        $a->setPlan($plan);

        $this->assertEquals($psy->getId(), $a->getPlan()?->getPsychologue()?->getId());
    }

    public function testOtherPsychologueDoesNotOwnAppointment(): void
    {
        $psy1 = $this->makePsychologue();
        $psy2 = new User();
        $psy2->setEmail('psy2@test.com');
        $psy2->setRole('Psychologue');

        $ref1 = new \ReflectionProperty(User::class, 'id');
        $ref1->setValue($psy1, 10);

        $ref2 = new \ReflectionProperty(User::class, 'id');
        $ref2->setValue($psy2, 20);

        $plan = $this->makePlan($psy1);
        $a    = new Appointment();
        $a->setPlan($plan);

        $this->assertNotEquals($psy2->getId(), $a->getPlan()?->getPsychologue()?->getId());
    }

    // -------------------------------------------------------------------------
    // Ownership : le patient ne peut annuler que ses propres RDV
    // -------------------------------------------------------------------------

    public function testPatientOwnsAppointment(): void
    {
        $patient = $this->makePatient();
        $ref     = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($patient, 5);

        $a = new Appointment();
        $a->setPatient($patient);

        $this->assertEquals($patient->getId(), $a->getPatient()?->getId());
    }

    public function testOtherPatientDoesNotOwnAppointment(): void
    {
        $p1 = $this->makePatient();
        $p2 = new User();
        $p2->setEmail('other@test.com');

        $ref1 = new \ReflectionProperty(User::class, 'id');
        $ref1->setValue($p1, 1);

        $ref2 = new \ReflectionProperty(User::class, 'id');
        $ref2->setValue($p2, 2);

        $a = new Appointment();
        $a->setPatient($p1);

        $this->assertNotEquals($p2->getId(), $a->getPatient()?->getId());
    }

    // -------------------------------------------------------------------------
    // Associations
    // -------------------------------------------------------------------------

    public function testSetAndGetPatient(): void
    {
        $patient = $this->makePatient();
        $a       = new Appointment();
        $a->setPatient($patient);

        $this->assertSame($patient, $a->getPatient());
    }

    public function testSetAndGetPlan(): void
    {
        $psy  = $this->makePsychologue();
        $plan = $this->makePlan($psy);
        $a    = new Appointment();
        $a->setPlan($plan);

        $this->assertSame($plan, $a->getPlan());
    }

    public function testSetCreatedAt(): void
    {
        $a    = new Appointment();
        $date = new \DateTimeImmutable('2026-01-15 10:00:00');
        $a->setCreatedAt($date);

        $this->assertSame($date, $a->getCreatedAt());
    }

    // -------------------------------------------------------------------------
    // Fluent interface
    // -------------------------------------------------------------------------

    public function testFluentInterface(): void
    {
        $patient = $this->makePatient();
        $psy     = $this->makePsychologue();
        $plan    = $this->makePlan($psy);
        $a       = new Appointment();

        $result = $a
            ->setPatient($patient)
            ->setPlan($plan)
            ->setStatus(Appointment::STATUS_CONFIRMED);

        $this->assertSame($a, $result);
    }
}
