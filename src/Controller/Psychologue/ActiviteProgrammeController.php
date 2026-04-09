<?php

namespace App\Controller\Psychologue;

use App\Entity\ActiviteProgramme;
use App\Entity\ProgrammeBienEtre;
use App\Form\ActiviteProgrammeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/psychologue/programme/{programmeId}/activite')]
final class ActiviteProgrammeController extends AbstractController
{
    #[Route('/new', name: 'app_psychologue_activite_new', methods: ['GET', 'POST'])]
    public function new(int $programmeId, Request $request, EntityManagerInterface $em): Response
    {
        $programme = $em->getRepository(ProgrammeBienEtre::class)->find($programmeId);
        if (!$programme) {
            throw $this->createNotFoundException('Programme introuvable');
        }

        $activite = new ActiviteProgramme();
        $activite->setProgramme($programme);
        $form = $this->createForm(ActiviteProgrammeType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($activite);
            $em->flush();

            $this->addFlash('success', 'Activité ajoutée avec succès !');

            return $this->redirectToRoute('app_psychologue_programme_show', ['id' => $programmeId]);
        }

        return $this->render('psychologue/activite_programme/new.html.twig', [
            'programme' => $programme,
            'activite' => $activite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_activite_edit', methods: ['GET', 'POST'])]
    public function edit(int $programmeId, ActiviteProgramme $activite, Request $request, EntityManagerInterface $em): Response
    {
        $programme = $em->getRepository(ProgrammeBienEtre::class)->find($programmeId);
        if (!$programme) {
            throw $this->createNotFoundException('Programme introuvable');
        }

        $form = $this->createForm(ActiviteProgrammeType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Activité modifiée avec succès !');

            return $this->redirectToRoute('app_psychologue_programme_show', ['id' => $programmeId]);
        }

        return $this->render('psychologue/activite_programme/edit.html.twig', [
            'programme' => $programme,
            'activite' => $activite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_psychologue_activite_delete', methods: ['POST'])]
    public function delete(int $programmeId, ActiviteProgramme $activite, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activite->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($activite);
            $em->flush();
            $this->addFlash('success', 'Activité supprimée.');
        }

        return $this->redirectToRoute('app_psychologue_programme_show', ['id' => $programmeId]);
    }
}
