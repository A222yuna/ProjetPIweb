<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use App\Service\PdfService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PdfController extends AbstractController
{
    private $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    #[Route('/appointment/{id}/pdf', name: 'app_appointment_pdf', methods: ['GET'])]
    public function appointmentPdf(int $id, AppointmentRepository $repository): Response
    {
        $appointment = $repository->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException('Rendez-vous introuvable.');
        }

        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isOwner = ($appointment->getPatient() === $user || $appointment->getPlan()->getPsychologue() === $user);

        if (!$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        // Generate QR Code for PDF
        $qrContent = sprintf(
            "RDV #%d\nPatient: %s\nPsy: %s\nDate: %s",
            $appointment->getId(),
            $appointment->getPatient()->getNom(),
            $appointment->getPlan()->getPsychologue()->getNom(),
            $appointment->getPlan()->getDayOfWeek()
        );
        $qrCode = new QrCode($qrContent);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        $qrCodeBase64 = $result->getDataUri();

        $pdfContent = $this->pdfService->generateAppointmentPdf($appointment, $qrCodeBase64);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="rdv-%d.pdf"', $id),
        ]);
    }

    #[Route('/patient/{id}/history/pdf', name: 'app_patient_history_pdf', methods: ['GET'])]
    public function patientHistoryPdf(int $id, UserRepository $userRepository, AppointmentRepository $appointmentRepository): Response
    {
        $patient = $userRepository->find($id);
        if (!$patient || !in_array('ROLE_PATIENT', $patient->getRoles())) {
            throw $this->createNotFoundException('Patient introuvable.');
        }

        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $user !== $patient) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $appointments = $appointmentRepository->findBy(['patient' => $patient], ['id' => 'DESC']);
        $pdfContent = $this->pdfService->generatePatientHistoryPdf($patient, $appointments);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="historique-patient-%s.pdf"', date('Y-m-d')),
        ]);
    }

    #[Route('/psychologue/{id}/history/pdf', name: 'app_psychologue_history_pdf', methods: ['GET'])]
    public function psychologueHistoryPdf(int $id, UserRepository $userRepository, AppointmentRepository $appointmentRepository): Response
    {
        $psychologue = $userRepository->find($id);
        if (!$psychologue || !in_array('ROLE_PSYCHOLOGUE', $psychologue->getRoles())) {
            throw $this->createNotFoundException('Psychologue introuvable.');
        }

        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $user !== $psychologue) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        // We need appointments for this psychologue. Since Appointment links to Plan, which links to Psychologue:
        $appointments = $appointmentRepository->createQueryBuilder('a')
            ->join('a.plan', 'p')
            ->where('p.psychologue = :psy')
            ->setParameter('psy', $psychologue)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();

        $pdfContent = $this->pdfService->generatePsychologueHistoryPdf($psychologue, $appointments);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="historique-psy-%s.pdf"', date('Y-m-d')),
        ]);
    }
}
