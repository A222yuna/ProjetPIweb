<?php

namespace App\Controller\Admin;

use App\Service\ExportService;
use App\Service\GoogleSheetsService;
use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/statistics')]
#[IsGranted('ROLE_ADMIN')]
class StatisticsController extends AbstractController
{
    #[Route('', name: 'app_admin_statistics')]
    public function index(StatisticsService $statisticsService): Response
    {
        return $this->render('admin/statistics/index.html.twig', [
            'roleChart' => $statisticsService->getUserRoleChart(),
            'statusChart' => $statisticsService->getUserStatusChart(),
        ]);
    }

    #[Route('/export', name: 'app_admin_statistics_export')]
    public function export(ExportService $exportService): Response
    {
        return $exportService->exportUsersToCsv();
    }

    #[Route('/export-google', name: 'app_admin_statistics_export_google', methods: ['POST'])]
    public function exportGoogle(Request $request, GoogleSheetsService $googleSheetsService): Response
    {
        $spreadsheetId = $request->request->get('spreadsheet_id');
        $accessToken = $request->request->get('access_token');

        if (!$spreadsheetId || !$accessToken) {
            $this->addFlash('error', 'Spreadsheet ID et Access Token sont requis.');
            return $this->redirectToRoute('app_admin_statistics');
        }

        $result = $googleSheetsService->exportToGoogleSheets($spreadsheetId, $accessToken);

        if (isset($result['error'])) {
            $this->addFlash('error', 'Erreur Google Sheets : ' . $result['error']);
        } else {
            $this->addFlash('success', 'Données exportées avec succès vers Google Sheets !');
        }

        return $this->redirectToRoute('app_admin_statistics');
    }
}
