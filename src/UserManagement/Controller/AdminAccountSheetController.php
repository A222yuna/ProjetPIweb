<?php

namespace App\UserManagement\Controller;

use App\Repository\UserRepository;
use App\UserManagement\Pdf\UserAccountPdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminAccountSheetController extends AbstractController
{
    /**
     * Fiche compte PDF (admin) — version simple.
     */
    #[Route('/{id}/fiche-pdf', name: 'app_admin_users_account_pdf', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function accountPdf(int $id, UserRepository $users, UserAccountPdfGenerator $pdf): Response
    {
        $user = $users->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $binary = $pdf->buildPdfBinary($user);

        $filename = sprintf('fiche-compte-%d.pdf', $user->getId());

        return new Response($binary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
