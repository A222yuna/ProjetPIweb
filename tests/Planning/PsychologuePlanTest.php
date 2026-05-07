<?php

namespace App\Tests\Planning;

use App\Entity\Appointment;
use App\Entity\PsychologuePlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests de l'entité PsychologuePlan (planning hebdomadaire).
 *
 * Couvre :
 *  - Valeurs par défaut
 *  - Setters / Getters
 *  - Validation des jours et périodes
 *  - Contraintes sur maxAppointments
 *  - Collection d'appointments
 */
class PsychologuePlanTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePsychologue(): User
    {
        $u = new User();
        $u->setNom('Psy');
        $u->setPrenom('Test');
        $u->setEmail('psy@test.com');
        $u->setRole('Psychologue');
        return $u;
    }

    // -------------------------------------------------------------------------
    // Valeurs par défaut
    // -------------------------------------------------------------------------

    public function testDefaultMaxAppointmentsIsFive(): void
    {
        $plan = new PsychologuePlan();
        $this->assertEquals(5, $plan->getMaxAppointments());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $plan = new PsychologuePlan();
        $this->assertInstanceOf(\DateTimeImmutable::class, $plan->getCreatedAt());
    }

    public function testIdIsNullByDefault(): void
    {
        $plan = new PsychologuePlan();
        $this->assertNull($plan->getId());
    }

    public function testAppointmentsCollectionIsEmptyByDefault(): void
    {
        $plan = new PsychologuePlan();
        $this->assertCount(0, $plan->getAppointments());
    }

    // -------------------------------------------------------------------------
    // Setters / Getters
    // -------------------------------------------------------------------------

    public function testSetAndGetPsychologue(): void
    {
        $plan = new PsychologuePlan();
        $psy  = $this->makePsychologue();

        $plan->setPsychologue($psy);

        $this->assertSame($psy, $plan->getPsychologue());
    }

    public function testSetAndGetDayOfWeek(): void
    {
        $plan = new PsychologuePlan();
        $plan->setDayOfWeek('WEDNESDAY');

        $this->assertEquals('WEDNESDAY', $plan->getDayOfWeek());
    }

    public function testSetAndGetPeriod(): void
    {
        $plan = new PsychologuePlan();
        $plan->setPeriod('NIGHT');

        $this->assertEquals('NIGHT', $plan->getPeriod());
    }

    public function testSetAndGetMaxAppointments(): void
    {
        $plan = new PsychologuePlan();
        $plan->setMaxAppointments(10);

        $this->assertEquals(10, $plan->getMaxAppointments());
    }

    public function testSetCreatedAt(): void
    {
        $plan = new PsychologuePlan();
        $date = new \DateTimeImmutable('2026-01-01 00:00:00');
        $plan->setCreatedAt($date);

        $this->assertSame($date, $plan->getCreatedAt());
    }

    // -------------------------------------------------------------------------
    // Constantes de jours et périodes
    // -------------------------------------------------------------------------

    public function testDayOfWeekChoicesContainsAllDays(): void
    {
        $expected = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
        $this->assertEquals($expected, PsychologuePlan::DAY_OF_WEEK_CHOICES);
    }

    public function testPeriodChoicesContainsDayAndNight(): void
    {
        $this->assertContains('DAY', PsychologuePlan::PERIOD_CHOICES);
        $this->assertContains('NIGHT', PsychologuePlan::PERIOD_CHOICES);
        $this->assertCount(2, PsychologuePlan::PERIOD_CHOICES);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validDaysProvider')]
    public function testAllValidDaysAreAccepted(string $day): void
    {
        $plan = new PsychologuePlan();
        $plan->setDayOfWeek($day);

        $this->assertContains($plan->getDayOfWeek(), PsychologuePlan::DAY_OF_WEEK_CHOICES);
    }

    /** @return array<string, array{string}> */
    public static function validDaysProvider(): array
    {
        return [
            'monday'    => ['MONDAY'],
            'tuesday'   => ['TUESDAY'],
            'wednesday' => ['WEDNESDAY'],
            'thursday'  => ['THURSDAY'],
            'friday'    => ['FRIDAY'],
            'saturday'  => ['SATURDAY'],
            'sunday'    => ['SUNDAY'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validPeriodsProvider')]
    public function testAllValidPeriodsAreAccepted(string $period): void
    {
        $plan = new PsychologuePlan();
        $plan->setPeriod($period);

        $this->assertContains($plan->getPeriod(), PsychologuePlan::PERIOD_CHOICES);
    }

    /** @return array<string, array{string}> */
    public static function validPeriodsProvider(): array
    {
        return [
            'day'   => ['DAY'],
            'night' => ['NIGHT'],
        ];
    }

    // -------------------------------------------------------------------------
    // Contraintes sur maxAppointments (1–20)
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('validMaxAppointmentsProvider')]
    public function testValidMaxAppointmentsRange(int $max): void
    {
        $plan = new PsychologuePlan();
        $plan->setMaxAppointments($max);

        $this->assertGreaterThanOrEqual(1, $plan->getMaxAppointments());
        $this->assertLessThanOrEqual(20, $plan->getMaxAppointments());
    }

    /** @return array<string, array{int}> */
    public static function validMaxAppointmentsProvider(): array
    {
        return [
            'min 1'  => [1],
            'mid 5'  => [5],
            'mid 10' => [10],
            'max 20' => [20],
        ];
    }

    // -------------------------------------------------------------------------
    // Scénario complet
    // -------------------------------------------------------------------------

    public function testFullPlanScenario(): void
    {
        $psy  = $this->makePsychologue();
        $plan = new PsychologuePlan();

        $plan->setPsychologue($psy);
        $plan->setDayOfWeek('FRIDAY');
        $plan->setPeriod('DAY');
        $plan->setMaxAppointments(8);

        $this->assertSame($psy, $plan->getPsychologue());
        $this->assertEquals('FRIDAY', $plan->getDayOfWeek());
        $this->assertEquals('DAY', $plan->getPeriod());
        $this->assertEquals(8, $plan->getMaxAppointments());
        $this->assertInstanceOf(\DateTimeImmutable::class, $plan->getCreatedAt());
        $this->assertCount(0, $plan->getAppointments());
    }

    // -------------------------------------------------------------------------
    // Fluent interface
    // -------------------------------------------------------------------------

    public function testFluentInterface(): void
    {
        $psy  = $this->makePsychologue();
        $plan = new PsychologuePlan();

        $result = $plan
            ->setPsychologue($psy)
            ->setDayOfWeek('TUESDAY')
            ->setPeriod('NIGHT')
            ->setMaxAppointments(3);

        $this->assertSame($plan, $result);
    }
}
