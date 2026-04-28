<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    private function generatePdf(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateAppointmentPdf(Appointment $appointment, string $qrCodeBase64): string
    {
        $html = $this->twig->render('pdf/appointment.html.twig', [
            'appointment' => $appointment,
            'qrCode' => $qrCodeBase64,
            'generatedAt' => new \DateTime(),
        ]);

        return $this->generatePdf($html);
    }

    public function generatePatientHistoryPdf(User $patient, array $appointments): string
    {
        $stats = [
            'total' => count($appointments),
            'completed' => count(array_filter($appointments, fn($a) => $a->getStatus() === Appointment::STATUS_COMPLETED)),
            'cancelled' => count(array_filter($appointments, fn($a) => $a->getStatus() === Appointment::STATUS_CANCELLED)),
            'paid' => count(array_filter($appointments, fn($a) => $a->getStatus() === Appointment::STATUS_PAID)),
        ];

        $html = $this->twig->render('pdf/patient_history.html.twig', [
            'patient' => $patient,
            'appointments' => $appointments,
            'stats' => $stats,
            'generatedAt' => new \DateTime(),
        ]);

        return $this->generatePdf($html);
    }

    public function generatePsychologueHistoryPdf(User $psychologue, array $appointments): string
    {
        $stats = [
            'total' => count($appointments),
            'completed' => count(array_filter($appointments, fn($a) => $a->getStatus() === Appointment::STATUS_COMPLETED)),
            'cancelled' => count(array_filter($appointments, fn($a) => $a->getStatus() === Appointment::STATUS_CANCELLED)),
            'paid' => count(array_filter($appointments, fn($a) => $a->getStatus() === Appointment::STATUS_PAID)),
        ];

        $html = $this->twig->render('pdf/psychologue_history.html.twig', [
            'psychologue' => $psychologue,
            'appointments' => $appointments,
            'stats' => $stats,
            'generatedAt' => new \DateTime(),
        ]);

        return $this->generatePdf($html);
    }
}
