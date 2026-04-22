<?php

namespace App\Service;

use App\Entity\ActiviteProgramme;
use App\Entity\ProgrammeBienEtre;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] private readonly string $mailerFrom
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $recipient = $user->getEmail();
        if (!$recipient) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($recipient)
            ->subject('Bienvenue chez MindCare !')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
                'subject' => 'Bienvenue chez MindCare',
                'message' => 'Nous sommes ravis de vous compter parmi nous ! Votre compte a été créé avec succès.',
            ]);

        $this->mailer->send($email);
    }

    public function sendProgrammeCreatedNotification(ProgrammeBienEtre $programme): void
    {
        $psychologueEmail = $programme->getPsychologue()?->getEmail();
        $recipients = array_values(array_unique(array_filter([$this->mailerFrom, $psychologueEmail])));
        if ($recipients === []) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to(...$recipients)
            ->subject('Nouveau programme bien-être créé')
            ->htmlTemplate('emails/notification.html.twig')
            ->context([
                'subject' => 'Nouveau programme bien-être',
                'message' => 'Un nouveau programme de bien-être vient d\'être ajouté à la plateforme.',
                'details' => [
                    'Nom du programme' => $programme->getNom() ?? 'N/A',
                    'Durée' => ($programme->getDuree() ?? '0') . ' jours',
                    'Niveau' => $programme->getNiveauDifficulte() ?? 'N/A',
                    'Statut' => $programme->getStatut() ?? 'N/A',
                ],
            ]);

        $this->mailer->send($email);
    }

    public function sendActivityAddedNotification(ProgrammeBienEtre $programme, ActiviteProgramme $activite): void
    {
        $psychologueEmail = $programme->getPsychologue()?->getEmail();
        $recipients = array_values(array_unique(array_filter([$this->mailerFrom, $psychologueEmail])));
        if ($recipients === []) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to(...$recipients)
            ->subject('Nouvelle activité ajoutée au programme')
            ->htmlTemplate('emails/notification.html.twig')
            ->context([
                'subject' => 'Nouvelle activité ajoutée',
                'message' => sprintf('Une nouvelle activité a été ajoutée au programme "%s".', $programme->getNom()),
                'details' => [
                    'Programme' => $programme->getNom() ?? 'N/A',
                    'Activité' => $activite->getTitre() ?? 'Sans titre',
                    'Jour' => 'Jour ' . $activite->getJour(),
                    'Heure' => $activite->getHeureDebut()?->format('H:i') ?? '--:--',
                    'Durée' => ($activite->getDureeMinutes() ?? 0) . ' min',
                ],
            ]);

        $this->mailer->send($email);
    }
}
