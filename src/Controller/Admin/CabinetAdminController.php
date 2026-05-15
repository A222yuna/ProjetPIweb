<?php

namespace App\Controller\Admin;

use App\Entity\Cabinet;
use App\Repository\CabinetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cabinets')]
#[IsGranted('ROLE_ADMIN')]
class CabinetAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_cabinets_index')]
    public function index(CabinetRepository $cabinetRepo): Response
    {
        return $this->render('admin/cabinet/index.html.twig', [
            'cabinets' => $cabinetRepo->findAll(),
        ]);
    }

    #[Route('/{id}/valider', name: 'app_admin_cabinets_validate', methods: ['POST'])]
    public function validate(Cabinet $cabinet, EntityManagerInterface $em): Response
    {
        $cabinet->setValide(true);
        $em->flush();
        $this->addFlash('success', 'Cabinet validé avec succès.');
        return $this->redirectToRoute('app_admin_cabinets_index');
    }

    #[Route('/{id}/archiver', name: 'app_admin_cabinets_archive', methods: ['POST'])]
    public function archive(Cabinet $cabinet, EntityManagerInterface $em): Response
    {
        $cabinet->setArchive(true);
        $em->flush();
        $this->addFlash('success', 'Cabinet archivé avec succès.');
        return $this->redirectToRoute('app_admin_cabinets_index');
    }

    #[Route('/{id}/rejeter', name: 'app_admin_cabinets_reject', methods: ['POST'])]
    public function reject(Cabinet $cabinet, \Symfony\Component\HttpFoundation\Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('reject_cabinet_'.$cabinet->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_cabinets_index');
        }

        $em->remove($cabinet);
        $em->flush();
        $this->addFlash('success', 'Cabinet rejeté et supprimé.');
        return $this->redirectToRoute('app_admin_cabinets_index');
    }
}
