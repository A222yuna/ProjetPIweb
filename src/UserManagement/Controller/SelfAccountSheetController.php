<?php

namespace App\UserManagement\Controller;

use App\Entity\User;
use App\UserManagement\Pdf\UserAccountPdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SelfAccountSheetController extends AbstractController
{
    #[Route('/psychologue/compte/fiche-pdf', name: 'app_psychologue_account_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psychologue(UserAccountPdfGenerator $pdf): Response
    {
        return $this->createSelfPdfResponse($pdf, 'psychologue');
    }

    #[Route('/patient/compte/fiche-pdf', name: 'app_patient_account_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_PATIENT')]
    public function patient(UserAccountPdfGenerator $pdf): Response
    {
        return $this->createSelfPdfResponse($pdf, 'patient');
    }

    private function createSelfPdfResponse(UserAccountPdfGenerator $pdf, string $prefix): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $binary = $pdf->buildPdfBinary($user);
        $filename = sprintf('fiche-compte-%s-%d.pdf', $prefix, $user->getId());

        return new Response($binary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
