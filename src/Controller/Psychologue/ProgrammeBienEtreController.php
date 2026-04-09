<?php

namespace App\Controller\Psychologue;

use App\Entity\ProgrammeBienEtre;
use App\Form\ProgrammeBienEtreType;
use App\Repository\ProgrammeBienEtreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads')] string $uploadDir): Response
    {
        $programme = new ProgrammeBienEtre();
        $form = $this->createForm(ProgrammeBienEtreType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($uploadDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
                $programme->setImage($newFilename);
            }

            $programme->setPsychologue($this->getUser());
            $em->persist($programme);
            $em->flush();

            $this->addFlash('success', 'Programme créé avec succès !');

            return $this->redirectToRoute('app_psychologue_programme_index');
        }

        return $this->render('psychologue/programme_bien_etre/new.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_psychologue_programme_show', methods: ['GET'])]
    public function show(ProgrammeBienEtre $programme): Response
    {
        return $this->render('psychologue/programme_bien_etre/show.html.twig', [
            'programme' => $programme,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_programme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProgrammeBienEtre $programme, EntityManagerInterface $em, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads')] string $uploadDir): Response
    {
        $form = $this->createForm(ProgrammeBienEtreType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($uploadDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
                $programme->setImage($newFilename);
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
