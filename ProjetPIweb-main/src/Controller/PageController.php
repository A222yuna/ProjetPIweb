<?php

namespace App\Controller;

use App\Repository\CabinetRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\ProgrammeBienEtreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages vitrine et maquette admin : même origine que le reste du site (pas d’autre port).
 */
final class PageController extends AbstractController
{
    #[Route('/le-cabinet', name: 'app_cabinet')]
    public function cabinet(
        ProgrammeBienEtreRepository $programmes,
        CabinetRepository $cabinets,
    ): Response
    {
        return $this->render('pages/cabinet.html.twig', [
            'programmes' => $programmes->findFrontCatalog(4),
            'cabinetRatings' => $cabinets->findRatingSummary(4),
        ]);
    }

    #[Route('/tarifs', name: 'app_tarifs')]
    public function tarifs(): Response
    {
        return $this->render('pages/tarifs.html.twig');
    }

    #[Route('/reservation', name: 'app_reservation')]
    public function reservation(
        DisponibiliteRepository $disponibilites,
        CabinetRepository $cabinets,
    ): Response
    {
        return $this->render('pages/reservation.html.twig', [
            'disponibilites' => $disponibilites->findWithCreneauxByCabinet(),
            'cabinetRatings' => $cabinets->findRatingSummary(4),
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }

    #[Route('/admin', name: 'app_admin_legacy')]
    public function adminLegacy(): Response
    {
        return $this->redirectToRoute('app_admin_dashboard');
    }
}
