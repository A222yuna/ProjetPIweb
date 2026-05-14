<?php

namespace App\Service;

use App\Entity\User;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    public function __construct(
        private \App\Repository\PsyCabinetRepository $psyCabinetRepo
    ) {}

    public function generatePsychologueQr(User $psy): string
    {
        $psyCabinet = $this->psyCabinetRepo->findOneBy(['psychologue' => $psy]);
        $cabinet = $psyCabinet?->getCabinet();

        $data = json_encode([
            'nom' => $psy->getNom(),
            'prenom' => $psy->getPrenom(),
            'email' => $psy->getEmail(),
            'telephone' => $psy->getTelephone(),
            'cabinet' => $cabinet ? $cabinet->getVille() . ', ' . $cabinet->getAdresse() : 'N/A',
            'plateforme' => 'PsyConnect'
        ]);

        $qrCode = new QrCode($data);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        return $result->getString();
    }
}