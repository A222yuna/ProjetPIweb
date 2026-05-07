<?php

namespace App\Tests\Entity;

use App\Entity\Appointment;
use App\Entity\User;
use App\Entity\PsychologuePlan;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase
{
    public function testAppointmentEntity(): void
    {
        $appointment = new Appointment();
        $patient = new User();
        $plan = new PsychologuePlan();

        $appointment->setPatient($patient);
        $appointment->setPlan($plan);
        $appointment->setStatus(Appointment::STATUS_CONFIRMED);

        $this->assertSame($patient, $appointment->getPatient());
        $this->assertSame($plan, $appointment->getPlan());
        $this->assertEquals(Appointment::STATUS_CONFIRMED, $appointment->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $appointment->getCreatedAt());
    }

    public function testDefaultStatus(): void
    {
        $appointment = new Appointment();
        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->getStatus());
    }
}
