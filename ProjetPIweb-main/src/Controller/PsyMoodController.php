<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Entity\EmotionAnalysis;
use App\Entity\User;
use App\Repository\CabinetRepository;
use App\Repository\EmotionAnalysisRepository;
use App\Service\PsyMoodAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psy-mood', name: 'psy_mood_')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class PsyMoodController extends AbstractController
{
    public function __construct(
        private PsyMoodAnalysisService    $moodService,
        private EmotionAnalysisRepository $analysisRepo,
        private CabinetRepository         $cabinetRepo,
        private EntityManagerInterface    $em
    ) {}

    // -------------------------------------------------------------------------
    // Dashboard principal
    // -------------------------------------------------------------------------
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Get cabinets linked to this psychologue via PsyCabinet
        $psyCabinets = $this->em->getRepository(\App\Entity\PsyCabinet::class)
            ->findBy(['psychologue' => $user]);
        $cabinets = array_map(fn($pc) => $pc->getCabinet(), $psyCabinets);

        // Fallback: if no PsyCabinet link exists, show all validated cabinets
        if (empty($cabinets)) {
            $cabinets = $this->cabinetRepo->findBy(['valide' => true, 'archive' => false]);
        }

        // Selected cabinet
        $cabinetId = $request->query->getInt('cabinet', 0);
        $selected  = null;
        $analysis  = null;

        if ($cabinetId > 0) {
            $selected = $this->em->getRepository(Cabinet::class)->find($cabinetId);
            if ($selected) {
                $analysis = $this->analysisRepo->findLatestByCabinet($selected);
            }
        } elseif (!empty($cabinets)) {
            $selected = $cabinets[0];
            $analysis = $this->analysisRepo->findLatestByCabinet($selected);
        }

        // Count alerts across all cabinets
        $alertCount = 0;
        foreach ($cabinets as $c) {
            $a = $this->analysisRepo->findLatestByCabinet($c);
            if ($a && $a->isAlerteActive()) $alertCount++;
        }

        return $this->render('psy_mood/index.html.twig', [
            'cabinets'    => $cabinets,
            'selected'    => $selected,
            'analysis'    => $analysis,
            'alert_count' => $alertCount,
        ]);
    }

    // -------------------------------------------------------------------------
    // Trigger analysis for a cabinet (POST)
    // -------------------------------------------------------------------------
    #[Route('/analyser/{id}', name: 'analyser', methods: ['POST'])]
    public function analyser(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('analyse_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('psy_mood_index');
        }

        $cabinet = $this->em->getRepository(Cabinet::class)->find($id);
        if (!$cabinet) {
            throw $this->createNotFoundException();
        }

        $this->moodService->analyseAndPersist($cabinet);
        $this->addFlash('success', 'Analyse émotionnelle mise à jour pour ' . $cabinet->getVille() . '.');

        return $this->redirectToRoute('psy_mood_index', ['cabinet' => $id]);
    }

    // -------------------------------------------------------------------------
    // API endpoint — returns analysis JSON for Chart.js
    // -------------------------------------------------------------------------
    #[Route('/api/{id}', name: 'api', methods: ['GET'])]
    public function api(int $id): JsonResponse
    {
        $cabinet  = $this->em->getRepository(Cabinet::class)->find($id);
        if (!$cabinet) {
            return $this->json(['error' => 'Cabinet introuvable'], 404);
        }

        $analysis = $this->analysisRepo->findLatestByCabinet($cabinet);
        if (!$analysis) {
            return $this->json(['error' => 'Aucune analyse disponible'], 404);
        }

        return $this->json([
            'cabinet'      => $cabinet->getVille(),
            'total'        => $analysis->getTotalReviews(),
            'sentiment'    => [
                'positif' => $analysis->getPositifPct(),
                'neutre'  => $analysis->getNeutrePct(),
                'negatif' => $analysis->getNegatifPct(),
            ],
            'emotions'     => [
                'Confiance'    => $analysis->getConfianceScore(),
                'Satisfaction' => $analysis->getSatisfactionScore(),
                'Anxiété'      => $analysis->getAnxieteScore(),
                'Déception'    => $analysis->getDeceptionScore(),
                'Stress'       => $analysis->getStressScore(),
                'Gratitude'    => $analysis->getGratitudeScore(),
            ],
            'top_mots'     => $analysis->getTopMots() ?? [],
            'alerte'       => $analysis->isAlerteActive(),
            'analysed_at'  => $analysis->getAnalysedAt()?->format('d/m/Y H:i'),
        ]);
    }
}
