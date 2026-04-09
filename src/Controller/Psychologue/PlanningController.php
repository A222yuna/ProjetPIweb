<?php

namespace App\Controller\Psychologue;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\DisponibiliteRepository;
use App\Repository\PsychologuePlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/planning')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class PlanningController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_planning_index', methods: ['GET'])]
    public function index(
        PsychologuePlanRepository $plans,
        DisponibiliteRepository $dispos,
        \App\Repository\AppointmentRepository $appointments,
        Request $request
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $page = max(1, $request->query->getInt('page', 1));
        $result = $appointments->findForPsychologuePaginated($user->getId() ?? 0, $page, 10);

        return $this->render('psychologue/planning/index.html.twig', [
            'plans' => $plans->findBy(['psychologue' => $user], ['id' => 'DESC']),
            'disponibilites' => $dispos->findForPsychologue($user),
            'appointments' => $result['items'],
            'page' => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / 10)),
        ]);
    }

    #[Route('/plan/new', name: 'app_psychologue_planning_plan_new', methods: ['GET', 'POST'])]
    public function newPlan(): Response
    {
        return $this->redirectToRoute('app_psychologue_plans_new');
    }

    #[Route('/disponibilite/new', name: 'app_psychologue_planning_dispo_new', methods: ['GET', 'POST'])]
    public function newDisponibilite(): Response
    {
        return $this->redirectToRoute('app_psychologue_disponibilites_new');
    }

    #[Route('/appointments/{id}/complete', name: 'app_psychologue_appointment_complete', methods: ['POST'])]
    public function completeAppointment(
        int $id,
        Request $request,
        \App\Repository\AppointmentRepository $appointments,
        \Doctrine\ORM\EntityManagerInterface $em
    ): Response {
        return $this->updateAppointmentStatus($id, Appointment::STATUS_COMPLETED, $request, $appointments, $em);
    }

    #[Route('/appointments/{id}/cancel', name: 'app_psychologue_appointment_cancel', methods: ['POST'])]
    public function cancelAppointment(
        int $id,
        Request $request,
        \App\Repository\AppointmentRepository $appointments,
        \Doctrine\ORM\EntityManagerInterface $em
    ): Response {
        return $this->updateAppointmentStatus($id, Appointment::STATUS_CANCELLED, $request, $appointments, $em);
    }

    private function updateAppointmentStatus(
        int $id,
        string $status,
        Request $request,
        \App\Repository\AppointmentRepository $appointments,
        \Doctrine\ORM\EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $appointment = $appointments->find($id);
        if (
            !$appointment
            || $appointment->getPlan()?->getPsychologue()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('psy_appointment_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_planning_index');
        }

        if ($appointment->getStatus() !== Appointment::STATUS_SCHEDULED) {
            $this->addFlash('warning', 'Seuls les rendez-vous SCHEDULED peuvent être modifiés.');
            return $this->redirectToRoute('app_psychologue_planning_index');
        }

        $appointment->setStatus($status);
        $em->flush();
        $this->addFlash('success', 'Statut du rendez-vous mis a jour: '.$status);

        return $this->redirectToRoute('app_psychologue_planning_index');
    }
}
