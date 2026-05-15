<?php

namespace App\Service;

use App\Entity\User;

class QrCodeService
{
    public function __construct(
        private \App\Repository\PsyCabinetRepository $psyCabinetRepo
    ) {}

    public function generatePsychologueQr(User $psy): string
    {
        // QR code generation disabled - endroid/qr-code not configured
        return '';
    }
}
