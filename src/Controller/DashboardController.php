<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;
use App\Repository\CabinetRepository;
use App\Repository\PostRepository;

final class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(UserRepository $userRepo, AppointmentRepository $appRepo, CabinetRepository $cabinetRepo): Response
    {
        return $this->render('dashboard/admin.html.twig', [
            'stats' => [
                'users' => $userRepo->count([]),
                'appointments' => $appRepo->count([]),
                'cabinets' => $cabinetRepo->count(['valide' => false]),
            ]
        ]);
    }

    #[Route('/psychologue/dashboard', name: 'app_psychologue_dashboard')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psychologue(
        \App\Repository\AppointmentRepository $appRepo,
        \App\Repository\DisponibiliteRepository $dispoRepo
    ): Response {
        $user = $this->getUser();
        return $this->render('dashboard/psychologue.html.twig', [
            'upcoming_appointments' => $appRepo->findForPsychologue($user->getId() ?? 0),
            'stats' => [
                'dispos_count' => count($dispoRepo->findForPsychologue($user)),
            ]
        ]);
    }

    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]
    #[IsGranted('ROLE_PATIENT')]
    public function patient(AppointmentRepository $appRepo): Response
    {
        $user = $this->getUser();
        return $this->render('dashboard/patient.html.twig', [
            'my_appointments' => $appRepo->findForPatient($user->getId() ?? 0),
        ]);
    }
}
