<?php

namespace App\Controller\Psychologue;

use App\Entity\ProgrammeBienEtre;
use App\Form\ProgrammeBienEtreType;
use App\Repository\ProgrammeBienEtreRepository;
use App\Service\CloudinaryUploader;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\GeminiService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/psychologue/programme')]
final class ProgrammeBienEtreController extends AbstractController
{
    #[Route('', name: 'app_psychologue_programme_index', methods: ['GET'])]
    public function index(ProgrammeBienEtreRepository $repo): Response
    {
        $user = $this->getUser();
        $programmes = $repo->findBy(['psychologue' => $user], ['id' => 'DESC']);

        return $this->render('psychologue/programme_bien_etre/index.html.twig', [
            'programmes' => $programmes,
        ]);
    }

    #[Route('/new', name: 'app_psychologue_programme_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CloudinaryUploader $cloudinaryUploader,
        MailService $mailService,
    ): Response
    {
        $programme = new ProgrammeBienEtre();
        $form = $this->createForm(ProgrammeBienEtreType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                try {
                    $imageUrl = $cloudinaryUploader->uploadProgrammeImage($imageFile);
                    if ($imageUrl !== '') {
                        $programme->setImage($imageUrl);
                    }
                } catch (\Throwable) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $programme->setPsychologue($this->getUser());
            $em->persist($programme);
            $em->flush();

            try {
                $mailService->sendProgrammeCreatedNotification($programme);
            } catch (TransportExceptionInterface) {
                $this->addFlash('warning', 'Programme créé, mais l\'envoi de l\'email a échoué.');
            }

            $this->addFlash('success', 'Programme créé avec succès !');

            return $this->redirectToRoute('app_psychologue_programme_index');
        }

        return $this->render('psychologue/programme_bien_etre/new.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/ai-generate', name: 'app_psychologue_programme_ai_generate', methods: ['POST'])]
    public function aiGenerate(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = $request->getPayload();
        $theme = $data->get('theme', 'Bien-être général');
        $days = (int) $data->get('days', 7);

        try {
            $result = $gemini->generateProgram($theme, $days);
            
            // Extract the actual JSON from Gemini's response
            $textResponse = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $programData = json_decode($textResponse, true);

            return $this->json($programData);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la génération : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'app_psychologue_programme_show', methods: ['GET'])]
    public function show(ProgrammeBienEtre $programme): Response
    {
        return $this->render('psychologue/programme_bien_etre/show.html.twig', [
            'programme' => $programme,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_programme_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ProgrammeBienEtre $programme,
        EntityManagerInterface $em,
        CloudinaryUploader $cloudinaryUploader
    ): Response
    {
        $form = $this->createForm(ProgrammeBienEtreType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                try {
                    $imageUrl = $cloudinaryUploader->uploadProgrammeImage($imageFile);
                    if ($imageUrl !== '') {
                        $programme->setImage($imageUrl);
                    }
                } catch (\Throwable) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Programme modifié avec succès !');

            return $this->redirectToRoute('app_psychologue_programme_show', ['id' => $programme->getId()]);
        }

        return $this->render('psychologue/programme_bien_etre/edit.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_psychologue_programme_delete', methods: ['POST'])]
    public function delete(Request $request, ProgrammeBienEtre $programme, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$programme->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($programme);
            $em->flush();
            $this->addFlash('success', 'Programme supprimé.');
        }

        return $this->redirectToRoute('app_psychologue_programme_index');
    }

    #[Route('/{id}/calendar-data', name: 'app_psychologue_programme_calendar_data', methods: ['GET'])]
    public function calendarData(ProgrammeBienEtre $programme): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $activites = [];
        foreach ($programme->getActivites() as $a) {
            $activites[] = [
                'id'           => $a->getId(),
                'jour'         => $a->getJour(),
                'heureDebut'   => $a->getHeureDebut() ? $a->getHeureDebut()->format('H:i') : null,
                'titre'        => $a->getTitre(),
                'description'  => $a->getDescription(),
                'dureeMinutes' => $a->getDureeMinutes(),
                'typeActivite' => $a->getTypeActivite(),
            ];
        }

        return $this->json([
            'programme' => [
                'id'               => $programme->getId(),
                'nom'              => $programme->getNom(),
                'duree'            => $programme->getDuree(),
                'statut'           => $programme->getStatut(),
                'niveauDifficulte' => $programme->getNiveauDifficulte(),
            ],
            'activites' => $activites,
        ]);
    }
}
