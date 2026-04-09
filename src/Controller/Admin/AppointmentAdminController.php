<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\AppointmentStatusType;

#[Route('/admin/appointments')]
#[IsGranted('ROLE_ADMIN')]
final class AppointmentAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_appointments_index', methods: ['GET'])]
    public function index(Request $request, AppointmentRepository $appointments): Response
    {
        $status = $request->query->getString('status');
        $dateStr = $request->query->getString('date');
        $date = $dateStr !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr) ?: null : null;
        $page = max(1, $request->query->getInt('page', 1));
        $result = $appointments->findAdminPaginatedWithDate($status !== '' ? $status : null, $date, $page, 15);

        return $this->render('admin/appointments/index.html.twig', [
            'appointments' => $result['items'],
            'status_filter' => $status,
            'date_filter' => $dateStr,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / 15)),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_appointments_show', methods: ['GET'])]
    public function show(int $id, AppointmentRepository $appointments): Response
    {
        $appointment = $appointments->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/appointments/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/update-status', name: 'app_admin_appointments_update_status', methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        AppointmentRepository $appointments,
        EntityManagerInterface $em
    ): Response {
        $appointment = $appointments->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('admin_appointment_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_appointments_index');
        }

        $form = $this->createForm(AppointmentStatusType::class, $appointment);
        $originalStatus = $appointment->getStatus();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Keep completed appointments immutable to avoid inconsistent workflow rewinds.
            if (
                $originalStatus === Appointment::STATUS_COMPLETED
                && $appointment->getStatus() !== Appointment::STATUS_COMPLETED
            ) {
                $this->addFlash('warning', 'Un rendez-vous COMPLETED ne peut pas revenir à un autre statut.');
                return $this->redirectToRoute('app_admin_appointments_index');
            }

            $em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_admin_appointments_index');
    }
}
