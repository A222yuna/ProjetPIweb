<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('dashboard/admin.html.twig');
    }

    #[Route('/psychologue/dashboard', name: 'app_psychologue_dashboard')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psychologue(): Response
    {
        return $this->render('dashboard/psychologue.html.twig');
    }

    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]
    #[IsGranted('ROLE_PATIENT')]
    public function patient(): Response
    {
        return $this->render('dashboard/patient.html.twig');
    }
}
