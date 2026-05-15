<?php

namespace App\Controller\Api;

use App\Service\DisponibiliteExportService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DisponibiliteExportController extends AbstractController
{
    /**
     * Export all disponibilites for a specific cabinet.
     * GET /api/disponibilites/export-excel/cabinet/{cabinetId}
     */
    #[Route(
        '/api/disponibilites/export-excel/cabinet/{cabinetId}',
        name: 'api_disponibilites_export_cabinet',
        methods: ['GET']
    )]
    public function exportByCabinet(int $cabinetId, DisponibiliteExportService $exportService): StreamedResponse
    {
        $spreadsheet = $exportService->exportByCabinet($cabinetId);
        $filename    = 'disponibilites_cabinet_' . $cabinetId . '_' . date('Y-m-d') . '.xlsx';

        return $this->buildResponse($spreadsheet, $filename);
    }

    /**
     * Export all disponibilites (all cabinets).
     * GET /api/disponibilites/export-excel/all
     */
    #[Route(
        '/api/disponibilites/export-excel/all',
        name: 'api_disponibilites_export_all',
        methods: ['GET']
    )]
    public function exportAll(DisponibiliteExportService $exportService): StreamedResponse
    {
        $spreadsheet = $exportService->exportAll();
        $filename    = 'disponibilites_all_' . date('Y-m-d') . '.xlsx';

        return $this->buildResponse($spreadsheet, $filename);
    }

    private function buildResponse(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
