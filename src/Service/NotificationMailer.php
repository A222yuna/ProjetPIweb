<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Creneau;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private QrCodeService $qr,
        private UrlGeneratorInterface $urlGenerator,
        private readonly string $fromEmail = 'noreply@psyconnect.com',
        private readonly string $fromName = 'PsyConnect'
    ) {}

    public function sendReservationNotificationToPsychologue(Appointment $appointment): void
    {
        $psychologue = $appointment->getPlan()->getPsychologue();
        $patient = $appointment->getPatient();

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

    public function sendConfirmation(
        Appointment $appointment,
        ?Creneau $creneau,
        User $psy,
        \App\Repository\PsyCabinetRepository $psyCabinetRepo
    ): void {
        $patient = $appointment->getPatient();
        if (!$patient?->getEmail()) return;

        $qrSvg = $this->qr->generatePsychologueQr($psy);
        
        $psyCabinet = $psyCabinetRepo->findOneBy(['psychologue' => $psy]);
        $cabinet = $psyCabinet?->getCabinet();

        $paymentUrl = $this->urlGenerator->generate(
            'app_patient_appointment_pay',
            ['id' => $appointment->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($patient->getEmail(),
                $patient->getPrenom().' '.$patient->getNom()))
            ->subject('✅ Rendez-vous Confirmé — PsyConnect')
            ->htmlTemplate('emails/appointment_confirmation.html.twig')
            ->context([
                'patient'     => $patient,
                'psy'         => $psy,
                'appointment' => $appointment,
                'date'  => $creneau?->getDateCreneau()?->format('d/m/Y') ?? 'N/A',
                'heure' => $creneau?->getHeure()?->format('H:i') ?? 'N/A',
                'cabinet'     => $cabinet,
                'qr_base64'   => base64_encode($qrSvg),
                'payment_url' => $paymentUrl,
            ])
            ->addPart(new DataPart(
                $qrSvg,
                'psychologue-qrcode.svg',
                'image/svg+xml'
            ));

        $this->mailer->send($email);
    }
}