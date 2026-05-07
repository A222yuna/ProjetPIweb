<?php

namespace App\Tests\Service;

use App\Entity\Appointment;
use App\Entity\PsychologuePlan;
use App\Entity\User;
use App\Service\NotificationMailer;
use App\Service\QrCodeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationMailerTest extends TestCase
{
    public function testSendReservationNotificationToPsychologue(): void
    {
        // 1. Mock dependencies
        $mailer = $this->createMock(MailerInterface::class);
        $qr = $this->createMock(QrCodeService::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        // 2. Setup objects
        $patient = new User();
        $patient->setEmail('patient@test.com');
        $patient->setPrenom('Jean');
        $patient->setNom('Patient');

        $psychologue = new User();
        $psychologue->setEmail('psy@test.com');
        $psychologue->setPrenom('Alice');
        $psychologue->setNom('Psychologue');

        $plan = new PsychologuePlan();
        $plan->setPsychologue($psychologue);

        $appointment = new Appointment();
        $appointment->setPatient($patient);
        $appointment->setPlan($plan);

        // 3. Define expectations
        // We expect mailer->send() to be called twice (one for psy, one for patient)
        $mailer->expects($this->exactly(2))
            ->method('send');

        // 4. Run the service method
        $notificationMailer = new NotificationMailer($mailer, $qr, $urlGenerator);
        $notificationMailer->sendReservationNotificationToPsychologue($appointment);
    }
}
