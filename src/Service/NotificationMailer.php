<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationMailer
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendReservationNotificationToPsychologue(Appointment $appointment): void
    {
        $psychologue = $appointment->getPlan()->getPsychologue();
        $patient = $appointment->getPatient();

        // Notify Psychologue
        $emailPsy = (new TemplatedEmail())
            ->from(new Address('no-reply@psychologie-app.com', 'Cabinet de Psychologie'))
            ->to(new Address($psychologue->getEmail(), $psychologue->getPrenom() . ' ' . $psychologue->getNom()))
            ->subject('Nouveau rendez-vous réservé')
            ->htmlTemplate('emails/new_reservation.html.twig')
            ->context([
                'appointment' => $appointment,
                'psychologue' => $psychologue,
                'patient' => $patient,
                'is_psy' => true,
            ]);

        $this->mailer->send($emailPsy);

        // Notify Patient (Confirmation)
        $emailPatient = (new TemplatedEmail())
            ->from(new Address('no-reply@psychologie-app.com', 'Cabinet de Psychologie'))
            ->to(new Address($patient->getEmail(), $patient->getPrenom() . ' ' . $patient->getNom()))
            ->subject('Confirmation de votre réservation')
            ->htmlTemplate('emails/new_reservation.html.twig')
            ->context([
                'appointment' => $appointment,
                'psychologue' => $psychologue,
                'patient' => $patient,
                'is_psy' => false,
            ]);

        $this->mailer->send($emailPatient);
    }

    public function sendStatusChangeNotificationToPatient(Appointment $appointment): void
    {
        $patient = $appointment->getPatient();
        $psychologue = $appointment->getPlan()->getPsychologue();

        // Notify Patient
        $emailPatient = (new TemplatedEmail())
            ->from(new Address('no-reply@psychologie-app.com', 'Cabinet de Psychologie'))
            ->to(new Address($patient->getEmail(), $patient->getPrenom() . ' ' . $patient->getNom()))
            ->subject('Mise à jour de votre rendez-vous')
            ->htmlTemplate('emails/status_change.html.twig')
            ->context([
                'appointment' => $appointment,
                'patient' => $patient,
                'psychologue' => $psychologue,
                'is_psy' => false,
            ]);

        $this->mailer->send($emailPatient);

        // Notify Psychologue
        $emailPsy = (new TemplatedEmail())
            ->from(new Address('no-reply@psychologie-app.com', 'Cabinet de Psychologie'))
            ->to(new Address($psychologue->getEmail(), $psychologue->getPrenom() . ' ' . $psychologue->getNom()))
            ->subject('Mise à jour du statut d\'un rendez-vous')
            ->htmlTemplate('emails/status_change.html.twig')
            ->context([
                'appointment' => $appointment,
                'patient' => $patient,
                'psychologue' => $psychologue,
                'is_psy' => true,
            ]);

        $this->mailer->send($emailPsy);
    }
}
