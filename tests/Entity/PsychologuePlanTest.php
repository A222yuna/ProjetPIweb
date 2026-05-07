<?php

namespace App\Tests\Entity;

use App\Entity\PsychologuePlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PsychologuePlanTest extends TestCase
{
    public function testPsychologuePlanEntity(): void
    {
        $plan = new PsychologuePlan();
        $psy = new User();

        $plan->setPsychologue($psy);
        $plan->setDayOfWeek('MONDAY');
        $plan->setPeriod('DAY');
        $plan->setMaxAppointments(10);

        $this->assertSame($psy, $plan->getPsychologue());
        $this->assertEquals('MONDAY', $plan->getDayOfWeek());
        $this->assertEquals('DAY', $plan->getPeriod());
        $this->assertEquals(10, $plan->getMaxAppointments());
        $this->assertInstanceOf(\DateTimeImmutable::class, $plan->getCreatedAt());
    }

    public function testDefaultMaxAppointments(): void
    {
        $plan = new PsychologuePlan();
        $this->assertEquals(5, $plan->getMaxAppointments());
    }
}
