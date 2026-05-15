<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Repository\CabinetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cabinets')]
final class CabinetFrontController extends AbstractController
{
    #[Route('/', name: 'app_cabinet_front_index', methods: ['GET'])]
    public function index(Request $request, CabinetRepository $cabinetRepository): Response
    {
        $search = trim($request->query->getString('q'));
        $cabinets = $cabinetRepository->findVisibleForPatients($search);
        $allVisible = $cabinetRepository->findVisibleForPatients(null);

        return $this->render('cabinet/front_index.html.twig', [
            'cabinets' => $cabinets,
            'search' => $search,
            'totalVisible' => \count($allVisible),
            'resultCount' => \count($cabinets),
        ]);
    }

    #[Route('/{id}', name: 'app_cabinet_front_show', methods: ['GET'])]
    public function show(Cabinet $cabinet): Response
    {
        return $this->render('cabinet/front_show.html.twig', [
            'cabinet' => $cabinet,
        ]);
    }
}
