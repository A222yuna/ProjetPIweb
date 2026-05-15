<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Creneau;
use App\Entity\User;

/**
 * Mail/QR notifications disabled — SMTP not configured.
 * All methods are no-ops so the rest of the app works normally.
 */
class NotificationMailer
{
    public function __construct(
        private readonly string $fromEmail = 'noreply@psyconnect.com',
        private readonly string $fromName = 'PsyConnect'
    ) {}

    public function sendReservationNotificationToPsychologue(Appointment $appointment): void
    {
        // Mail disabled
    }

    public function sendStatusChangeNotificationToPatient(Appointment $appointment): void
    {
        // Mail disabled
    }

    public function sendConfirmation(
        Appointment $appointment,
        ?Creneau $creneau,
        User $psy,
        \App\Repository\PsyCabinetRepository $psyCabinetRepo
    ): void {
        // Mail disabled
    }
}
