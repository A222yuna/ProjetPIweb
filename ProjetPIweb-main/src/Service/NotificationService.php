<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Cabinet;
use App\Entity\Creneau;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Twig\Environment;

class NotificationService
{
    /** Fixed recipient for all emails and SMS (project owner) */
    private const FIXED_EMAIL = 'ghazelmaram18@gmail.com';
    private const FIXED_PHONE = '+21699076402';

    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface        $mailer,
        private TexterInterface        $texter,
        private Environment            $twig,
        private string                 $mailerFrom = 'ghazelmaram18@gmail.com'
    ) {}

    // =========================================================================
    // CORE: Create in-app notification
    // =========================================================================

    public function create(
        User    $recipient,
        string  $type,
        string  $title,
        string  $message,
        ?string $link = null
    ): Notification {
        $n = new Notification();
        $n->setRecipient($recipient);
        $n->setType($type);
        $n->setTitle($title);
        $n->setMessage($message);
        $n->setLink($link);

        $this->em->persist($n);
        $this->em->flush();

        return $n;
    }

    // =========================================================================
    // CORE: Send email to fixed recipient
    // =========================================================================

    public function sendEmail(
        string $subject,
        string $template,
        array  $context = []
    ): void {
        try {
            $html = $this->twig->render($template, $context);
            $email = (new Email())
                ->from($this->mailerFrom)
                ->to(self::FIXED_EMAIL)
                ->subject($subject)
                ->html($html);
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log('[NotificationService] Email error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // CORE: Send SMS to fixed phone number
    // =========================================================================

    public function sendSms(string $message): void
    {
        try {
            $sms = new SmsMessage(self::FIXED_PHONE, $message);
            $this->texter->send($sms);
        } catch (\Throwable $e) {
            error_log('[NotificationService] SMS error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // TRIGGER 1 — Appointment created
    // =========================================================================

    public function notifyAppointmentCreated(Appointment $appointment): void
    {
        $patient = $appointment->getPatient();
        $plan    = $appointment->getPlan();
        $psy     = $plan?->getPsychologue();
        $date    = $appointment->getCreatedAt()?->format('d/m/Y');

        if ($patient) {
            $this->create($patient, 'rdv_new', '✅ Rendez-vous confirmé',
                'Votre rendez-vous du ' . $date . ' a été enregistré.',
                '/patient/rendezvous/');

            $this->sendEmail(
                '✅ Confirmation de votre rendez-vous',
                'emails/rdv_confirmation.html.twig',
                ['patient' => $patient, 'appointment' => $appointment]
            );

            $this->sendSms(
                '[Cabinet Psy] RDV confirmé le ' . $date .
                '. Connectez-vous sur http://127.0.0.1:8000 pour les détails.'
            );
        }

        if ($psy) {
            $this->create($psy, 'rdv_new', '📅 Nouveau rendez-vous',
                ($patient ? $patient->getPrenom() . ' ' . $patient->getNom() : 'Un patient') . ' a pris rendez-vous.',
                '/psychologue/planning/');
        }
    }

    // =========================================================================
    // TRIGGER 2 — Appointment cancelled
    // =========================================================================

    public function notifyAppointmentCancelled(Appointment $appointment): void
    {
        $patient = $appointment->getPatient();
        $psy     = $appointment->getPlan()?->getPsychologue();
        $date    = $appointment->getCreatedAt()?->format('d/m/Y');

        if ($patient) {
            $this->create($patient, 'rdv_cancelled', '❌ Rendez-vous annulé',
                'Votre rendez-vous du ' . $date . ' a été annulé.',
                '/patient/rendezvous/');

            $this->sendEmail(
                '❌ Votre rendez-vous a été annulé',
                'emails/rdv_cancelled.html.twig',
                ['patient' => $patient, 'appointment' => $appointment]
            );

            $this->sendSms('[Cabinet Psy] Votre RDV du ' . $date . ' a été annulé.');
        }

        if ($psy) {
            $this->create($psy, 'rdv_cancelled', '❌ RDV annulé',
                ($patient ? $patient->getPrenom() . ' ' . $patient->getNom() : 'Un patient') . ' a annulé son rendez-vous.',
                '/psychologue/planning/');
        }
    }

    // =========================================================================
    // TRIGGER 3 — Creneau reserved
    // =========================================================================

    public function notifyCreneauReserved(Creneau $creneau): void
    {
        $patient = $creneau->getPatient();
        $date    = $creneau->getDateCreneau()?->format('d/m/Y');
        $heure   = $creneau->getHeure()?->format('H:i');
        $cabinet = $creneau->getDisponibilite()?->getCabinet();

        if ($patient) {
            $this->create($patient, 'rdv_new', '✅ Créneau réservé',
                'Votre créneau du ' . $date . ' à ' . $heure . ' est confirmé.',
                '/patient/rendezvous/');

            $this->sendEmail(
                '✅ Confirmation de votre créneau',
                'emails/rdv_confirmation.html.twig',
                ['patient' => $patient, 'date' => $date, 'heure' => $heure, 'cabinet' => $cabinet]
            );

            $this->sendSms(
                '[Cabinet Psy] Créneau réservé le ' . $date . ' à ' . $heure .
                ($cabinet ? ' — Cabinet ' . $cabinet->getVille() : '') . '.'
            );
        }
    }

    // =========================================================================
    // TRIGGER 4 — Creneau cancelled
    // =========================================================================

    public function notifyCreneauAnnule(Creneau $creneau): void
    {
        $patient = $creneau->getPatient();
        $date    = $creneau->getDateCreneau()?->format('d/m/Y');
        $heure   = $creneau->getHeure()?->format('H:i');

        if ($patient) {
            $this->create($patient, 'rdv_cancelled', '❌ Créneau annulé',
                'Votre créneau du ' . $date . ' à ' . $heure . ' a été annulé.',
                '/patient/rendezvous/');

            $this->sendSms('[Cabinet Psy] Votre créneau du ' . $date . ' à ' . $heure . ' a été annulé.');
        }
    }

    // =========================================================================
    // TRIGGER 5 — Cabinet validated
    // =========================================================================

    public function notifyCabinetValidated(Cabinet $cabinet): void
    {
        foreach ($cabinet->getPsyCabinets() as $psyCabinet) {
            $psy = $psyCabinet->getPsychologue();
            if (!$psy) continue;

            $this->create($psy, 'cabinet_validated', '🎉 Cabinet validé !',
                'Votre cabinet à ' . $cabinet->getVille() . ' a été validé.',
                '/psychologue/cabinets/' . $cabinet->getId());

            $this->sendEmail(
                '🎉 Votre cabinet a été validé !',
                'emails/cabinet_validated.html.twig',
                ['psychologue' => $psy, 'cabinet' => $cabinet]
            );

            $this->sendSms(
                '[Cabinet Psy] Votre cabinet à ' . $cabinet->getVille() . ' a été validé par l\'administrateur.'
            );
        }
    }

    // =========================================================================
    // TRIGGER 6 — New message
    // =========================================================================

    public function notifyNewMessage(Message $message): void
    {
        $destinataire = $message->getDestinataire();
        $expediteur   = $message->getExpediteur();

        if ($destinataire && $expediteur) {
            $this->create($destinataire, 'new_message', '💬 Nouveau message',
                $expediteur->getPrenom() . ' ' . $expediteur->getNom() . ' vous a envoyé un message.',
                '/messages');

            $this->sendSms(
                '[Cabinet Psy] Nouveau message de ' .
                $expediteur->getPrenom() . ' ' . $expediteur->getNom() . '.'
            );
        }
    }

    // =========================================================================
    // TRIGGER 7 — New rating submitted
    // Rating has: getPatient(), getCabinet()
    // =========================================================================

    public function notifyNewRating(Rating $rating): void
    {
        $cabinet = $rating->getCabinet();
        $patient = $rating->getPatient();

        // 1. Notify the patient — confirmation of their rating
        if ($patient) {
            $this->create(
                $patient,
                'new_avis',
                '⭐ Avis enregistré',
                'Votre avis sur le cabinet ' . ($cabinet?->getVille() ?? '') . ' a bien été pris en compte.',
                '/cabinet/' . ($cabinet?->getId() ?? '') . '/rating'
            );
        }

        if (!$cabinet) return;

        // 2. Notify psychologues via PsyCabinet link
        $notified = 0;
        foreach ($cabinet->getPsyCabinets() as $psyCabinet) {
            $psy = $psyCabinet->getPsychologue();
            if (!$psy) continue;

            $this->create($psy, 'new_avis', '⭐ Nouvel avis reçu',
                'Un patient a laissé un avis sur votre cabinet à ' . $cabinet->getVille() . '.',
                '/psychologue/cabinets/' . $cabinet->getId());
            $notified++;
        }

        // 3. Fallback: notify all Psychologue users + send SMS once
        if ($notified === 0) {
            $psychologues = $this->em->createQuery(
                'SELECT u FROM App\Entity\User u WHERE u.role = :role'
            )->setParameter('role', 'Psychologue')->getResult();

            foreach ($psychologues as $psy) {
                if ($patient && $psy->getId() === $patient->getId()) continue;
                $this->create($psy, 'new_avis', '⭐ Nouvel avis reçu',
                    'Un patient a laissé un avis sur le cabinet ' . $cabinet->getVille() . '.',
                    '/psychologue/cabinets/' . $cabinet->getId());
            }
        }

        // SMS alert for new rating
        $this->sendSms(
            '[Cabinet Psy] Nouvel avis reçu sur le cabinet ' . $cabinet->getVille() .
            '. Note : ' . ($rating->getNoteGlobale() ?? $rating->getNote()) . '/5.'
        );
    }
}
