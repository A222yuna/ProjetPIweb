<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Entity\Rating;
use App\Repository\RatingRepository;
use App\Service\RatingCalculatorService;
use App\Service\ReputationCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cabinet', name: 'cabinet_')]
class RatingController extends AbstractController
{
    public function __construct(
        private RatingCalculatorService    $ratingCalculator,
        private ReputationCalculatorService $reputationCalculator,
        private RatingRepository           $ratingRepository,
        private EntityManagerInterface     $em
    ) {}

    // -------------------------------------------------------------------------
    // Page d'avis d'un cabinet
    // -------------------------------------------------------------------------
    #[Route('/{id}/rating', name: 'rating', methods: ['GET'])]
    public function show(int $id): Response
    {
        $cabinet = $this->em->getRepository(Cabinet::class)->find($id);
        if (!$cabinet) {
            throw $this->createNotFoundException('Cabinet introuvable');
        }

        $stats          = $this->ratingCalculator->getCabinetRatingStats($cabinet);
        $existingRating = null;
        $canRate        = false;
        $consultCount   = 0;

        $user = $this->getUser();
        if ($user) {
            $existingRating = $this->ratingRepository->findByPatientAndCabinet($user, $cabinet);
            $canRate        = $this->ratingRepository->hasPatientConsulted($user, $cabinet);
            $consultCount   = $this->ratingRepository->countPatientConsultations($user, $cabinet);
        }

        return $this->render('rating/cabinet_rating.html.twig', [
            'cabinet'         => $cabinet,
            'stats'           => $stats,
            'existing_rating' => $existingRating,
            'can_rate'        => $canRate,
            'consult_count'   => $consultCount,
        ]);
    }

    // -------------------------------------------------------------------------
    // Soumettre / modifier un avis
    // -------------------------------------------------------------------------
    #[Route('/{id}/rating/submit', name: 'rating_submit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submit(int $id, Request $request): Response
    {
        $cabinet = $this->em->getRepository(Cabinet::class)->find($id);
        if (!$cabinet) {
            throw $this->createNotFoundException();
        }

        // CSRF
        if (!$this->isCsrfTokenValid('rating_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('cabinet_rating', ['id' => $id]);
        }

        $user = $this->getUser();

        // ── BUSINESS RULE: patient must have a real consultation ──────────────
        if (!$this->ratingRepository->hasPatientConsulted($user, $cabinet)) {
            $this->addFlash('error', 'Vous ne pouvez noter ce cabinet que si vous y avez effectué une consultation.');
            return $this->redirectToRoute('cabinet_rating', ['id' => $id]);
        }

        // Upsert: one rating per patient+cabinet (UniqueConstraint)
        $existing = $this->ratingRepository->findByPatientAndCabinet($user, $cabinet);
        $rating   = $existing ?? new Rating();

        $rating->setPatient($user);
        $rating->setCabinet($cabinet);

        // Validate & clamp notes 1–5
        $rating->setNoteEcoute((float)        max(1, min(5, (int) $request->request->get('note_ecoute', 3))));
        $rating->setNoteCompetence((float)    max(1, min(5, (int) $request->request->get('note_competence', 3))));
        $rating->setNotePonctualite((float)   max(1, min(5, (int) $request->request->get('note_ponctualite', 3))));
        $rating->setNoteEnvironnement((float) max(1, min(5, (int) $request->request->get('note_environnement', 3))));
        $rating->setCommentaireRating(trim((string) $request->request->get('commentaire', '')));

        // Mark as verified (patient has a real consultation)
        $rating->setIsVerified(true);

        // Compute weighted global note and sync the legacy `note` int field
        $this->ratingCalculator->prepareRating($rating);
        $rating->setNote((int) round($rating->getNoteGlobale() ?? 3));

        if (!$existing) {
            $this->em->persist($rating);
        }
        $this->em->flush();

        // Recalculate and persist cabinet reputation score immediately
        $this->reputationCalculator->updateAndPersist($cabinet);

        $this->addFlash('success', $existing ? 'Votre avis a été mis à jour.' : 'Merci pour votre avis !');
        return $this->redirectToRoute('cabinet_rating', ['id' => $id]);
    }

    // -------------------------------------------------------------------------
    // API — stats JSON
    // -------------------------------------------------------------------------
    #[Route('/{id}/rating-stats', name: 'rating_stats_api', methods: ['GET'])]
    public function statsApi(int $id): JsonResponse
    {
        $cabinet = $this->em->getRepository(Cabinet::class)->find($id);
        if (!$cabinet) {
            return $this->json(['error' => 'Cabinet introuvable'], 404);
        }

        return $this->json($this->ratingCalculator->getCabinetRatingStats($cabinet));
    }
}
