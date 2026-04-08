<?php

namespace App\Controller\Psychologue;

use App\Entity\Disponibilite;
use App\Entity\User;
use App\Form\DisponibiliteType;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/disponibilites')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class DisponibiliteController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_disponibilites_index', methods: ['GET'])]
    public function index(DisponibiliteRepository $disponibilites): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        return $this->render('psychologue/disponibilites/index.html.twig', [
            'disponibilites' => $disponibilites->findForPsychologue($user),
        ]);
    }

    #[Route('/new', name: 'app_psychologue_disponibilites_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, DisponibiliteRepository $disponibilites): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $disponibilite = new Disponibilite();
        $form = $this->createForm(DisponibiliteType::class, $disponibilite, [
            'psychologue_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cabinetId = $disponibilite->getCabinet()?->getId();
            if (!$cabinetId || !$disponibilites->canManageCabinetId($user, $cabinetId)) {
                $this->addFlash('error', 'Ce cabinet ne vous appartient pas.');
                return $this->redirectToRoute('app_psychologue_disponibilites_new');
            }
            // RULE 2 — heure_fin after heure_debut
            if ($disponibilite->getHeureFin() && $disponibilite->getHeureDebut() && $disponibilite->getHeureFin() <= $disponibilite->getHeureDebut()) {
                $this->addFlash('error', "L'heure de fin doit être strictement après l'heure de début");
                return $this->redirectToRoute('app_psychologue_disponibilites_new');
            }

            // RULE 3 — Duration fits window (warning only)
            $start = $disponibilite->getHeureDebut();
            $end = $disponibilite->getHeureFin();
            if ($start && $end) {
                $totalMinutes = ((int) $end->format('H')) * 60 + (int) $end->format('i') - (((int) $start->format('H')) * 60 + (int) $start->format('i'));
                if ($disponibilite->getDureeConsultation() > 0 && $totalMinutes > 0 && $totalMinutes % $disponibilite->getDureeConsultation() !== 0) {
                    $this->addFlash('warning', 'La durée de consultation ne divise pas exactement le créneau horaire');
                }
            }

            $em->persist($disponibilite);
            $em->flush();
            $this->addFlash('success', 'Disponibilité créée');

            return $this->redirectToRoute('app_psychologue_disponibilites_index');
        }

        return $this->render('psychologue/disponibilites/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_disponibilites_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, DisponibiliteRepository $disponibilites, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $disponibilite = $disponibilites->find($id);
        if (!$disponibilite) {
            throw $this->createNotFoundException();
        }
        // RULE 10 — ownership
        if (!$disponibilites->isOwnedByPsychologue($disponibilite, $user)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DisponibiliteType::class, $disponibilite, [
            'psychologue_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cabinetId = $disponibilite->getCabinet()?->getId();
            if (!$cabinetId || !$disponibilites->canManageCabinetId($user, $cabinetId)) {
                $this->addFlash('error', 'Ce cabinet ne vous appartient pas.');
                return $this->redirectToRoute('app_psychologue_disponibilites_edit', ['id' => $disponibilite->getId()]);
            }
            if ($disponibilite->getHeureFin() && $disponibilite->getHeureDebut() && $disponibilite->getHeureFin() <= $disponibilite->getHeureDebut()) {
                $this->addFlash('error', "L'heure de fin doit être strictement après l'heure de début");
                return $this->redirectToRoute('app_psychologue_disponibilites_edit', ['id' => $disponibilite->getId()]);
            }

            $start = $disponibilite->getHeureDebut();
            $end = $disponibilite->getHeureFin();
            if ($start && $end) {
                $totalMinutes = ((int) $end->format('H')) * 60 + (int) $end->format('i') - (((int) $start->format('H')) * 60 + (int) $start->format('i'));
                if ($disponibilite->getDureeConsultation() > 0 && $totalMinutes > 0 && $totalMinutes % $disponibilite->getDureeConsultation() !== 0) {
                    $this->addFlash('warning', 'La durée de consultation ne divise pas exactement le créneau horaire');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Disponibilité mise à jour');
            return $this->redirectToRoute('app_psychologue_disponibilites_index');
        }

        return $this->render('psychologue/disponibilites/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_psychologue_disponibilites_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, DisponibiliteRepository $disponibilites, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $disponibilite = $disponibilites->find($id);
        if (!$disponibilite) {
            throw $this->createNotFoundException();
        }
        if (!$disponibilites->isOwnedByPsychologue($disponibilite, $user)) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('delete_disponibilite_'.$disponibilite->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_disponibilites_index');
        }

        $em->remove($disponibilite);
        $em->flush();
        $this->addFlash('success', 'Disponibilité supprimée');

        return $this->redirectToRoute('app_psychologue_disponibilites_index');
    }
}

