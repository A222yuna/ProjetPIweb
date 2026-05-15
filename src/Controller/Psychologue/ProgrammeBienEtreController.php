<?php

namespace App\Controller\Psychologue;

use App\Entity\ProgrammeBienEtre;
use App\Entity\ActiviteProgramme;
use App\Form\ProgrammeBienEtreType;
use App\Form\ActiviteProgrammeType;
use App\Repository\ProgrammeBienEtreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/programmes')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class ProgrammeBienEtreController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_programmes_index', methods: ['GET'])]
    public function index(ProgrammeBienEtreRepository $repo): Response
    {
        return $this->render('psychologue/programme_bien_etre/index.html.twig', [
            'programmes' => $repo->findBy(['psychologue' => $this->getUser()]),
        ]);
    }

    #[Route('/nouveau', name: 'app_psychologue_programmes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $programme = new ProgrammeBienEtre();
        $programme->setPsychologue($this->getUser());
        
        $form = $this->createForm(ProgrammeBienEtreType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($programme);
            $em->flush();
            $this->addFlash('success', 'Programme créé avec succès.');
            return $this->redirectToRoute('app_psychologue_programmes_index');
        }

        return $this->render('psychologue/programme_bien_etre/new.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_psychologue_programmes_show', methods: ['GET'])]
    public function show(ProgrammeBienEtre $programme): Response
    {
        if ($programme->getPsychologue() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('psychologue/programme_bien_etre/show.html.twig', [
            'programme' => $programme,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_programmes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProgrammeBienEtre $programme, EntityManagerInterface $em): Response
    {
        if ($programme->getPsychologue() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProgrammeBienEtreType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Programme mis à jour.');
            return $this->redirectToRoute('app_psychologue_programmes_index');
        }

        return $this->render('psychologue/programme_bien_etre/edit.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activite/nouvelle', name: 'app_psychologue_activite_new', methods: ['GET', 'POST'])]
    public function newActivite(Request $request, ProgrammeBienEtre $programme, EntityManagerInterface $em): Response
    {
        if ($programme->getPsychologue() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $activite = new ActiviteProgramme();
        $activite->setProgramme($programme);
        
        $form = $this->createForm(ActiviteProgrammeType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($activite);
            $em->flush();
            $this->addFlash('success', 'Activité ajoutée.');
            return $this->redirectToRoute('app_psychologue_programmes_show', ['id' => $programme->getId()]);
        }

        return $this->render('psychologue/activite_programme/new.html.twig', [
            'programme' => $programme,
            'activite' => $activite,
            'form' => $form,
        ]);
    }
}
