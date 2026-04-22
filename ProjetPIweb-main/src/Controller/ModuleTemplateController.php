<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ModuleTemplateController extends AbstractController
{
    private function renderModule(string $space, string $module): Response
    {
        return $this->render(sprintf('modules/%s/module.html.twig', $space), [
            'module' => $module,
        ]);
    }

    #[Route('/admin/modules/comptes', name: 'app_admin_module_comptes')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminComptes(): Response { return $this->renderModule('admin', 'comptes'); }

    #[Route('/admin/modules/consultations', name: 'app_admin_module_consultations')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminConsultations(): Response { return $this->renderModule('admin', 'consultations'); }

    #[Route('/admin/modules/cabinets', name: 'app_admin_module_cabinets')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminCabinets(): Response { return $this->redirectToRoute('app_cabinet_index'); }

    #[Route('/admin/modules/bien-etre', name: 'app_admin_module_bienetre')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminBienetre(): Response { return $this->renderModule('admin', 'bienetre'); }

    #[Route('/admin/modules/messages', name: 'app_admin_module_messages')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminMessages(): Response { return $this->renderModule('admin', 'messages'); }

    #[Route('/admin/modules/forum', name: 'app_admin_module_forum')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminForum(): Response { return $this->renderModule('admin', 'forum'); }

    #[Route('/psychologue/modules/comptes', name: 'app_psy_module_comptes')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psyComptes(): Response { return $this->renderModule('psychologue', 'comptes'); }

    #[Route('/psychologue/modules/consultations', name: 'app_psy_module_consultations')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psyConsultations(): Response { return $this->renderModule('psychologue', 'consultations'); }

    #[Route('/psychologue/modules/cabinets', name: 'app_psy_module_cabinets')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psyCabinets(): Response { return $this->redirectToRoute('app_psy_cabinet_index'); }

    #[Route('/psychologue/modules/bien-etre', name: 'app_psy_module_bienetre')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psyBienetre(): Response { return $this->renderModule('psychologue', 'bienetre'); }

    #[Route('/psychologue/modules/messages', name: 'app_psy_module_messages')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psyMessages(): Response { return $this->renderModule('psychologue', 'messages'); }

    #[Route('/psychologue/modules/forum', name: 'app_psy_module_forum')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psyForum(): Response { return $this->renderModule('psychologue', 'forum'); }

    #[Route('/patient/modules/comptes', name: 'app_patient_module_comptes')]
    #[IsGranted('ROLE_PATIENT')]
    public function patientComptes(): Response { return $this->renderModule('patient', 'comptes'); }

    #[Route('/patient/modules/consultations', name: 'app_patient_module_consultations')]
    #[IsGranted('ROLE_PATIENT')]
    public function patientConsultations(): Response { return $this->renderModule('patient', 'consultations'); }

    #[Route('/patient/modules/cabinets', name: 'app_patient_module_cabinets')]
    #[IsGranted('ROLE_PATIENT')]
    public function patientCabinets(): Response { return $this->redirectToRoute('app_cabinet_front_index'); }

    #[Route('/patient/modules/bien-etre', name: 'app_patient_module_bienetre')]
    #[IsGranted('ROLE_PATIENT')]
    public function patientBienetre(): Response { return $this->renderModule('patient', 'bienetre'); }

    #[Route('/patient/modules/messages', name: 'app_patient_module_messages')]
    #[IsGranted('ROLE_PATIENT')]
    public function patientMessages(): Response { return $this->renderModule('patient', 'messages'); }

    #[Route('/patient/modules/forum', name: 'app_patient_module_forum')]
    #[IsGranted('ROLE_PATIENT')]
    public function patientForum(): Response { return $this->renderModule('patient', 'forum'); }
}
