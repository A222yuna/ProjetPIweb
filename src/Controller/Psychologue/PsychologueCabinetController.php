<?php

namespace App\Controller\Psychologue;

use App\Entity\Cabinet;
use App\Entity\PsyCabinet;
use App\Form\CabinetType;
use App\Repository\PsyCabinetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/cabinets')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class PsychologueCabinetController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_cabinets_index', methods: ['GET'])]
    public function index(PsyCabinetRepository $repo): Response
    {
        return $this->render('psychologue/cabinet/index.html.twig', [
            'psyCabinets' => $repo->findBy(['psychologue' => $this->getUser()]),
        ]);
    }

    #[Route('/nouveau', name: 'app_psychologue_cabinets_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $cabinet = new Cabinet();
        $form = $this->createForm(CabinetType::class, $cabinet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cabinet->setValide(false); // Admin must validate
            $em->persist($cabinet);

            $psyCabinet = new PsyCabinet();
            $psyCabinet->setPsychologue($this->getUser());
            $psyCabinet->setCabinet($cabinet);
            $em->persist($psyCabinet);

            $em->flush();
            $this->addFlash('success', 'Cabinet créé. Il sera visible après validation par un administrateur.');
            return $this->redirectToRoute('app_psychologue_cabinets_index');
        }

        return $this->render('psychologue/cabinet/new.html.twig', [
            'cabinet' => $cabinet,
            'form' => $form,
        ]);
    }
}
