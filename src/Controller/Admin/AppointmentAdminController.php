<?php

namespace App\Controller\Admin;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/appointments')]
#[IsGranted('ROLE_ADMIN')]
final class AppointmentAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_appointments_index', methods: ['GET'])]
    public function index(Request $request, AppointmentRepository $appointments): Response
    {
        $status = $request->query->getString('status');
        $page = max(1, $request->query->getInt('page', 1));
        $result = $appointments->findAdminPaginated($status !== '' ? $status : null, $page, 6);

        return $this->render('admin/appointments/index.html.twig', [
            'appointments' => $result['items'],
            'status_filter' => $status,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / 6)),
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_admin_appointments_status', methods: ['POST'])]
    public function setStatus(
        int $id,
        string $status,
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
        if (!\in_array($status, ['SCHEDULED', 'COMPLETED', 'CANCELLED'], true)) {
            $this->addFlash('warning', 'Statut invalide.');
            return $this->redirectToRoute('app_admin_appointments_index');
        }
        $appointment->setStatus($status);
        $em->flush();
        $this->addFlash('success', 'Statut mis a jour.');

        return $this->redirectToRoute('app_admin_appointments_index');
    }
}
