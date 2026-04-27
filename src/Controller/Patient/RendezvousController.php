<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\User;
use App\Form\PatientAppointmentType;
use App\Service\NotificationMailer;
use App\Repository\AppointmentRepository;
use App\Repository\PsychologuePlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient/rendezvous')]
#[IsGranted('ROLE_PATIENT')]
final class RendezvousController extends AbstractController
{
    #[Route('/', name: 'app_patient_rendezvous_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        AppointmentRepository $appointments,
        PsychologuePlanRepository $plans,
        NotificationMailer $mailer
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $page = max(1, $request->query->getInt('page', 1));

        $appointment = new Appointment();
        $form = $this->createForm(PatientAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plan = $appointment->getPlan();
            if (!$plan) {
                $this->addFlash('error', 'Veuillez sélectionner un planning valide.');
                return $this->redirectToRoute('app_patient_rendezvous_index');
            }

            if ($appointments->hasActiveAppointmentForPatientAndPlan($user, $plan)) {
                $this->addFlash('error', 'Vous avez déjà un rendez-vous SCHEDULED pour ce planning.');
                return $this->redirectToRoute('app_patient_rendezvous_index');
            }

            if ($appointments->countScheduledForPlan($plan) >= $plan->getMaxAppointments()) {
                $this->addFlash('error', 'Ce psychologue a atteint son nombre maximum de rendez-vous');
                return $this->redirectToRoute('app_patient_rendezvous_index');
            }

            $appointment->setPatient($user);
            $appointment->setStatus(Appointment::STATUS_SCHEDULED);
            $em->persist($appointment);
            $em->flush();

            try {
                $mailer->sendReservationNotificationToPsychologue($appointment);
            } catch (\Exception $e) {
                // Log error or ignore
            }

            $this->addFlash('success', 'Rendez-vous reserve avec succes.');

            return $this->redirectToRoute('app_patient_rendezvous_index');
        }

        $result = $appointments->findForPatientPaginated($user->getId() ?? 0, $page, 10);

        return $this->render('patient/rendezvous/index.html.twig', [
            'form' => $form,
            'plans' => $plans->findBy([], ['id' => 'DESC']),
            'appointments' => $result['items'],
            'page' => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / 10)),
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_patient_rendezvous_cancel', methods: ['POST'])]
    public function cancel(
        int $id,
        Request $request,
        AppointmentRepository $appointments,
        EntityManagerInterface $em,
        NotificationMailer $mailer
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $appointment = $appointments->find($id);
        if (!$appointment || $appointment->getPatient()?->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('cancel_appointment_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_patient_rendezvous_index');
        }
        if ($appointment->getStatus() !== Appointment::STATUS_SCHEDULED) {
            $this->addFlash('warning', 'Ce rendez-vous ne peut plus etre annule.');
            return $this->redirectToRoute('app_patient_rendezvous_index');
        }

        $appointment->setStatus(Appointment::STATUS_CANCELLED);
        $em->flush();

        try {
            $mailer->sendReservationNotificationToPsychologue($appointment);
        } catch (\Exception $e) {
            // Log error or ignore
        }

        $this->addFlash('success', 'Rendez-vous annule.');

        return $this->redirectToRoute('app_patient_rendezvous_index');
    }
}
