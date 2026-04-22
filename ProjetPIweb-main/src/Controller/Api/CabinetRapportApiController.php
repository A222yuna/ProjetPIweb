<?php

namespace App\Controller\Api;

use App\Repository\CabinetRepository;
use App\Service\CabinetRapportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CabinetRapportApiController extends AbstractController
{
    #[Route('/api/cabinets/{id}/rapport-pdf', name: 'api_cabinet_rapport_pdf', methods: ['GET'])]
    public function generatePdf(
        int $id,
        CabinetRepository $cabinetRepository,
        CabinetRapportService $rapportService
    ): Response {
        $cabinet = $cabinetRepository->find($id);

        if (!$cabinet) {
            return $this->json(['error' => 'Cabinet non trouvé'], 404);
        }

        $stats = $rapportService->getCabinetStats($id);

        $html = $this->renderView('pdf/rapport_cabinet.html.twig', [
            'cabinet' => $cabinet,
            'stats'   => $stats,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('rapport_cabinet_%d_%s.pdf', $id, date('Y-m-d'));

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
