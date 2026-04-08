<?php

namespace App\Controller\Psychologue;

use App\Entity\PsychologuePlan;
use App\Entity\User;
use App\Form\PsychologuePlanType;
use App\Repository\PsychologuePlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/plans')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class PsychologuePlanController extends AbstractController
{
    #[Route('/', name: 'app_psychologue_plans_index', methods: ['GET'])]
    public function index(PsychologuePlanRepository $plans): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        return $this->render('psychologue/plans/index.html.twig', [
            'plans' => $plans->findForPsychologue($user),
        ]);
    }

    #[Route('/new', name: 'app_psychologue_plans_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PsychologuePlanRepository $plans, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $plan = new PsychologuePlan();
        $form = $this->createForm(PsychologuePlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // RULE 1 — No duplicate plan
            if ($plans->existsDuplicatePlan($user, (string) $plan->getDayOfWeek(), (string) $plan->getPeriod())) {
                $this->addFlash('error', 'Vous avez déjà un planning pour ce jour et cette période');
                return $this->redirectToRoute('app_psychologue_plans_new');
            }

            $plan->setPsychologue($user);
            $em->persist($plan);
            $em->flush();

            $this->addFlash('success', 'Planning créé avec succès');
            return $this->redirectToRoute('app_psychologue_plans_index');
        }

        return $this->render('psychologue/plans/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_plans_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, PsychologuePlanRepository $plans, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $plan = $plans->find($id);
        if (!$plan) {
            throw $this->createNotFoundException();
        }
        // RULE 10 — ownership
        if ($plan->getPsychologue()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PsychologuePlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plans->existsDuplicatePlan($user, (string) $plan->getDayOfWeek(), (string) $plan->getPeriod(), $plan->getId())) {
                $this->addFlash('error', 'Vous avez déjà un planning pour ce jour et cette période');
                return $this->redirectToRoute('app_psychologue_plans_edit', ['id' => $plan->getId()]);
            }

            $em->flush();
            $this->addFlash('success', 'Planning mis à jour');
            return $this->redirectToRoute('app_psychologue_plans_index');
        }

        return $this->render('psychologue/plans/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'plan' => $plan,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_psychologue_plans_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, PsychologuePlanRepository $plans, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $plan = $plans->find($id);
        if (!$plan) {
            throw $this->createNotFoundException();
        }
        if ($plan->getPsychologue()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('delete_plan_'.$plan->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_plans_index');
        }

        $em->remove($plan);
        $em->flush();
        $this->addFlash('success', 'Planning supprimé');

        return $this->redirectToRoute('app_psychologue_plans_index');
    }
}

