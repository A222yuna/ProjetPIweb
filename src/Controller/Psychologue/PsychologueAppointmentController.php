<?php

namespace App\Controller\Psychologue;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\CreneauRepository;
use App\Service\NotificationMailer;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/appointments')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class PsychologueAppointmentController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_appointments_index', methods: ['GET'])]
    public function index(Request $request, AppointmentRepository $appointments): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $status = $request->query->get('status');
        $patientName = $request->query->get('patient');

        $queryBuilder = $appointments->createQueryBuilder('a')
            ->leftJoin('a.plan', 'p')
            ->leftJoin('p.psychologue', 'psy')
            ->leftJoin('a.patient', 'pt')
            ->andWhere('psy.id = :psyId')
            ->setParameter('psyId', $user->getId());

        if ($status) {
            $queryBuilder->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($patientName) {
            $queryBuilder->andWhere('pt.nom LIKE :patient OR pt.prenom LIKE :patient')
                ->setParameter('patient', '%' . $patientName . '%');
        }

        return $this->render('psychologue/appointments/index.html.twig', [
            'appointments' => $queryBuilder->getQuery()->getResult(),
            'current_status' => $status,
            'search_patient' => $patientName,
        ]);
    }

    #[Route('/{id}/complete', name: 'app_psychologue_appointments_complete', methods: ['POST'])]
    public function complete(int $id, Request $request, AppointmentRepository $appointments, EntityManagerInterface $em, NotificationMailer $mailer): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $appointment = $appointments->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException();
        }
        // ownership via plan psychologue
        if ($appointment->getPlan()?->getPsychologue()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('complete_appointment_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_appointments_index');
        }

        if ($appointment->getStatus() !== Appointment::STATUS_SCHEDULED) {
            $this->addFlash('warning', 'Seuls les rendez-vous SCHEDULED peuvent être terminés.');
            return $this->redirectToRoute('app_psychologue_appointments_index');
        }

        $appointment->setStatus(Appointment::STATUS_COMPLETED);
        $em->flush();

        try {
            $mailer->sendStatusChangeNotificationToPatient($appointment);
        } catch (\Exception $e) {
            // Log error or ignore
        }

        $this->addFlash('success', 'Rendez-vous marqué comme terminé.');

        return $this->redirectToRoute('app_psychologue_appointments_index');
    }

    #[Route('/{id}/confirm', name: 'app_psychologue_appointments_confirm', methods: ['POST'])]
    public function confirm(
        int $id, 
        Request $request, 
        AppointmentRepository $appointments, 
        CreneauRepository $creneauRepo, 
        \App\Repository\PsyCabinetRepository $psyCabinetRepo,
        EntityManagerInterface $em, 
        NotificationMailer $mailer
    ): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $appointment = $appointments->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException();
        }
        if ($appointment->getPlan()?->getPsychologue()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('confirm_appointment_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_appointments_index');
        }

        if ($appointment->getStatus() !== Appointment::STATUS_SCHEDULED) {
            $this->addFlash('warning', 'Seuls les rendez-vous SCHEDULED peuvent être confirmés.');
            return $this->redirectToRoute('app_psychologue_appointments_index');
        }

        $appointment->setStatus(Appointment::STATUS_CONFIRMED);
        $em->flush();

        $creneau = $creneauRepo->findLatestForPatientAndPlan(
            $appointment->getPatient(),
            $appointment->getPlan()
        );
        try {
            $mailer->sendConfirmation($appointment, $creneau, $user, $psyCabinetRepo);
            $this->addFlash('success', 'RDV confirmé ✓ Email + QR Code envoyé au patient.');
        } catch (\Exception $e) {
            $this->addFlash('success', 'RDV confirmé ✓');
            $this->addFlash('warning', 'Email non envoyé: '.$e->getMessage());
        }

        return $this->redirectToRoute('app_psychologue_appointments_index');
    }
}

