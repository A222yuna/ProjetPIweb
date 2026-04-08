<?php

namespace App\Controller\Psychologue;

use App\Entity\Cabinet;
use App\Entity\Disponibilite;
use App\Form\CabinetType;
use App\Form\DisponibiliteType;
use App\Repository\CabinetRepository;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/cabinets')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class CabinetController extends AbstractController
{
    #[Route('/', name: 'app_psy_cabinet_index', methods: ['GET'])]
    public function index(CabinetRepository $cabinetRepository): Response
    {
        return $this->render('cabinet/index.html.twig', [
            'cabinets' => $cabinetRepository->findBy([], ['id' => 'DESC']),
            'stats' => $cabinetRepository->getDashboardStats(),
            'cabinet_route_prefix' => 'app_psy_cabinet_',
            'disponibilite_route_prefix' => 'app_psy_disponibilite_',
        ]);
    }

    #[Route('/new', name: 'app_psy_cabinet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cabinet = new Cabinet();
        $form = $this->createForm(CabinetType::class, $cabinet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cabinet);
            $entityManager->flush();
            $this->addFlash('success', 'Cabinet ajoute avec succes.');

            return $this->redirectToRoute('app_psy_cabinet_index');
        }

        return $this->render('cabinet/new.html.twig', [
            'cabinet' => $cabinet,
            'form' => $form,
            'index_route' => 'app_psy_cabinet_index',
        ]);
    }

    #[Route('/{id}', name: 'app_psy_cabinet_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Cabinet $cabinet, DisponibiliteRepository $disponibiliteRepository): Response
    {
        return $this->render('cabinet/show.html.twig', [
            'cabinet' => $cabinet,
            'disponibilites' => $disponibiliteRepository->findByCabinetOrdered($cabinet->getId() ?? 0),
            'index_route' => 'app_psy_cabinet_index',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psy_cabinet_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Cabinet $cabinet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CabinetType::class, $cabinet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Cabinet modifie avec succes.');

            return $this->redirectToRoute('app_psy_cabinet_index');
        }

        return $this->render('cabinet/edit.html.twig', [
            'cabinet' => $cabinet,
            'form' => $form,
            'index_route' => 'app_psy_cabinet_index',
        ]);
    }

    #[Route('/{id}', name: 'app_psy_cabinet_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Cabinet $cabinet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_cabinet_'.$cabinet->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($cabinet);
            $entityManager->flush();
            $this->addFlash('success', 'Cabinet supprime avec succes.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_psy_cabinet_index');
    }

    #[Route('/{id}/valider', name: 'app_psy_cabinet_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validateCabinet(): Response
    {
        throw $this->createAccessDeniedException('Seul l administrateur peut valider un cabinet.');
    }

    #[Route('/{id}/archiver', name: 'app_psy_cabinet_archive', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archive(): Response
    {
        throw $this->createAccessDeniedException('Seul l administrateur peut archiver un cabinet.');
    }

    #[Route('/disponibilites', name: 'app_psy_disponibilite_index', methods: ['GET'])]
    public function disponibiliteIndex(DisponibiliteRepository $disponibiliteRepository): Response
    {
        return $this->render('disponibilite/index.html.twig', [
            'disponibilites' => $disponibiliteRepository->findBy([], ['jour' => 'ASC', 'heureDebut' => 'ASC']),
            'cabinet_index_route' => 'app_psy_cabinet_index',
            'disponibilite_route_prefix' => 'app_psy_disponibilite_',
        ]);
    }

    #[Route('/disponibilites/new', name: 'app_psy_disponibilite_new', methods: ['GET', 'POST'])]
    public function disponibiliteNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $disponibilite = new Disponibilite();
        $form = $this->createForm(DisponibiliteType::class, $disponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($disponibilite);
            $entityManager->flush();
            $this->addFlash('success', 'Disponibilite ajoutee avec succes.');

            return $this->redirectToRoute('app_psy_disponibilite_index');
        }

        return $this->render('disponibilite/new.html.twig', [
            'form' => $form,
            'index_route' => 'app_psy_disponibilite_index',
        ]);
    }

    #[Route('/disponibilites/{id}/edit', name: 'app_psy_disponibilite_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function disponibiliteEdit(Request $request, Disponibilite $disponibilite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DisponibiliteType::class, $disponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Disponibilite modifiee avec succes.');

            return $this->redirectToRoute('app_psy_disponibilite_index');
        }

        return $this->render('disponibilite/edit.html.twig', [
            'form' => $form,
            'disponibilite' => $disponibilite,
            'index_route' => 'app_psy_disponibilite_index',
        ]);
    }

    #[Route('/disponibilites/{id}', name: 'app_psy_disponibilite_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function disponibiliteDelete(Request $request, Disponibilite $disponibilite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_disponibilite_'.$disponibilite->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($disponibilite);
            $entityManager->flush();
            $this->addFlash('success', 'Disponibilite supprimee avec succes.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_psy_disponibilite_index');
    }
}
