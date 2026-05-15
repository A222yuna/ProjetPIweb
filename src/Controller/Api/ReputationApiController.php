<?php

namespace App\Controller\Api;

use App\Entity\Cabinet;
use App\Service\ReputationCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ReputationApiController extends AbstractController
{
    public function __construct(
        private ReputationCalculatorService $reputationCalculator,
        private EntityManagerInterface      $em
    ) {}

    /**
     * GET /api/cabinets/{id}/reputation
     * Returns the full reputation breakdown for a cabinet.
     */
    #[Route('/cabinets/{id}/reputation', name: 'cabinet_reputation', methods: ['GET'])]
    public function getReputation(int $id): JsonResponse
    {
        $cabinet = $this->em->getRepository(Cabinet::class)->find($id);

        if (!$cabinet) {
            return $this->json(['success' => false, 'error' => 'Cabinet introuvable'], 404);
        }

        $result = $this->reputationCalculator->calculate($cabinet);

        return $this->json([
            'success'    => true,
            'cabinet_id' => $id,
            'ville'      => $cabinet->getVille(),
            'score'      => $result['score'],
            'badge'      => $result['badge'],
            'emoji'      => $result['emoji'],
            'color'      => $result['color'],
            'breakdown'  => $result['breakdown'],
            'weights'    => $result['weights'],
            'meta'       => $result['meta'],
            'updated_at' => $cabinet->getScoreUpdatedAt()?->format('d/m/Y H:i'),
        ]);
    }

    /**
     * POST /api/cabinets/{id}/reputation/refresh
     * Force-recalculate and persist the score for one cabinet.
     */
    #[Route('/cabinets/{id}/reputation/refresh', name: 'cabinet_reputation_refresh', methods: ['POST'])]
    public function refreshReputation(int $id): JsonResponse
    {
        $cabinet = $this->em->getRepository(Cabinet::class)->find($id);

        if (!$cabinet) {
            return $this->json(['success' => false, 'error' => 'Cabinet introuvable'], 404);
        }

        $result = $this->reputationCalculator->updateAndPersist($cabinet);

        return $this->json([
            'success' => true,
            'score'   => $result['score'],
            'badge'   => $result['badge'],
            'emoji'   => $result['emoji'],
        ]);
    }
}
