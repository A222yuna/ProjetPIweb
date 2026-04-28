<?php

namespace App\Controller\Psychologue;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\PsyCabinetRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/statistiques')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class StatisticsController extends AbstractController
{
    #[Route('', name: 'app_psychologue_statistiques', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $psyId = $user->getId();

        $allAppointments = $appointmentRepository->findForPsychologue($psyId);
        $totalAppointments = count($allAppointments);

        // Stats by status
        $statusCounts = $appointmentRepository->countByStatusForPsy($psyId);
        $statsByStatus = [
            'SCHEDULED' => 0,
            'CONFIRMED' => 0,
            'COMPLETED' => 0,
            'CANCELLED' => 0,
            'PAID' => 0,
        ];
        foreach ($statusCounts as $row) {
            if (isset($statsByStatus[$row['status']])) {
                $statsByStatus[$row['status']] = (int) $row['count'];
            }
        }

        // Stats by month (last 12)
        $monthCounts = $appointmentRepository->countByMonthForPsy($psyId);
        $statsByMonth = [];
        $currentDate = new \DateTimeImmutable('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $monthKey = $currentDate->format('Y-m');
            $statsByMonth[$monthKey] = 0;
            $currentDate = $currentDate->modify('+1 month');
        }
        foreach ($monthCounts as $row) {
            if (isset($statsByMonth[$row['month']])) {
                $statsByMonth[$row['month']] = (int) $row['count'];
            }
        }

        // Top patients
        $topPatients = $appointmentRepository->getTopPatientsForPsy($psyId, 5);

        // Stats by day of week
        $dayCounts = $appointmentRepository->countByDayOfWeekForPsy($psyId);
        $statsByDay = [
            'MONDAY' => 0,
            'TUESDAY' => 0,
            'WEDNESDAY' => 0,
            'THURSDAY' => 0,
            'FRIDAY' => 0,
            'SATURDAY' => 0,
            'SUNDAY' => 0,
        ];
        foreach ($dayCounts as $row) {
            if (isset($statsByDay[$row['dayOfWeek']])) {
                $statsByDay[$row['dayOfWeek']] = (int) $row['count'];
            }
        }

        return $this->render('psychologue/statistiques/index.html.twig', [
            'totalAppointments' => $totalAppointments,
            'statsByStatus' => $statsByStatus,
            'statsByMonth' => $statsByMonth,
            'monthLabels' => array_keys($statsByMonth),
            'monthData' => array_values($statsByMonth),
            'topPatients' => $topPatients,
            'statsByDay' => $statsByDay,
            'dayLabels' => array_keys($statsByDay),
            'dayData' => array_values($statsByDay),
            'statusLabels' => array_keys($statsByStatus),
            'statusData' => array_values($statsByStatus),
        ]);
    }

    #[Route('/export', name: 'app_psychologue_statistiques_export', methods: ['GET'])]
    public function exportExcel(AppointmentRepository $appointmentRepository, PsyCabinetRepository $psyCabinetRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $psyId = $user->getId();
        $appointments = $appointmentRepository->findForPsychologue($psyId);
        
        $psyCabinet = $psyCabinetRepo->findOneBy(['psychologue' => $user]);
        $cabinetName = $psyCabinet?->getCabinet()?->getNom() ?? 'N/A';

        $spreadsheet = new Spreadsheet();

        // --- Sheet 1: Rendez-vous ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rendez-vous');
        $headers = ['ID', 'Patient', 'Jour RDV', 'Statut', 'Cabinet', 'Créé le'];
        $sheet->fromArray([$headers], null, 'A1');

        $rowNum = 2;
        foreach ($appointments as $app) {
            $plan = $app->getPlan();
            $patient = $app->getPatient();
            
            $sheet->fromArray([[
                $app->getId(),
                $patient ? $patient->getNom() . ' ' . $patient->getPrenom() : 'N/A',
                $plan ? $plan->getDayOfWeek() : 'N/A',
                $app->getStatus(),
                $cabinetName,
                $app->getCreatedAt()->format('d/m/Y H:i')
            ]], null, 'A' . $rowNum);
            $rowNum++;
        }

        // --- Sheet 2: Statistiques ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Statistiques');
        
        $statusCounts = $appointmentRepository->countByStatusForPsy($psyId);
        $stats = ['Total RDV' => count($appointments)];
        $totalCompleted = 0;
        foreach ($statusCounts as $row) {
            $stats[$row['status']] = (int) $row['count'];
            if ($row['status'] === Appointment::STATUS_COMPLETED) $totalCompleted = (int)$row['count'];
        }
        
        $statsData = [];
        foreach ($stats as $label => $value) {
            $statsData[] = [$label, $value];
        }
        $completionRate = count($appointments) > 0 ? round(($totalCompleted / count($appointments)) * 100, 2) : 0;
        $statsData[] = ['Taux completion', $completionRate . '%'];
        
        $sheet2->fromArray($statsData, null, 'A1');

        // --- Sheet 3: Patients ---
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Patients');
        $headers3 = ['Patient', 'Email', 'Nombre RDV', 'Dernier RDV'];
        $sheet3->fromArray([$headers3], null, 'A1');
        
        $topPatients = $appointmentRepository->getTopPatientsForPsy($psyId, 1000);
        $rowNum3 = 2;
        foreach ($topPatients as $tp) {
            $sheet3->fromArray([[
                $tp['nom'] . ' ' . $tp['prenom'],
                $tp['email'],
                $tp['count'],
                $tp['lastAppointment'] instanceof \DateTimeInterface ? $tp['lastAppointment']->format('d/m/Y H:i') : 'N/A'
            ]], null, 'A' . $rowNum3);
            $rowNum3++;
        }

        // --- Global Styling ---
        foreach ($spreadsheet->getAllSheets() as $currentSheet) {
            $highestColumn = $currentSheet->getHighestColumn();
            $headerRange = 'A1:' . $highestColumn . '1';
            $currentSheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C6BA0']]
            ]);

            $highestRow = $currentSheet->getHighestRow();
            for ($i = 2; $i <= $highestRow; $i++) {
                if ($i % 2 === 0) {
                    $currentSheet->getStyle('A' . $i . ':' . $highestColumn . $i)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F0EEF8');
                }
            }
            
            foreach (range('A', $highestColumn) as $col) {
                $currentSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'psychologue-rdv-' . date('Y-m-d') . '.xlsx';

        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName),
        ]);
    }
}