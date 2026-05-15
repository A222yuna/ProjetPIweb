<?php

namespace App\Service;

use App\Repository\DisponibiliteRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DisponibiliteExportService
{
    private const JOURS = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
        4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];

    public function __construct(private DisponibiliteRepository $disponibiliteRepo) {}

    /**
     * Export all disponibilites for a given cabinet.
     * Disponibilite has: getJour(), getHeureDebut(), getHeureFin(),
     *                    getDureeConsultation(), getCabinet(), getCreneaux()
     */
    public function exportByCabinet(int $cabinetId): Spreadsheet
    {
        $disponibilites = $this->disponibiliteRepo->findWithCreneauxByCabinet($cabinetId);
        return $this->buildSpreadsheet($disponibilites, 'Cabinet #' . $cabinetId);
    }

    /**
     * Export all disponibilites (all cabinets).
     */
    public function exportAll(): Spreadsheet
    {
        $disponibilites = $this->disponibiliteRepo->findWithCreneauxByCabinet();
        return $this->buildSpreadsheet($disponibilites, 'Tous les cabinets');
    }

    private function buildSpreadsheet(array $disponibilites, string $subtitle): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Disponibilités');

        // ── Header style ──────────────────────────────────────────────────────
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF6B3FA0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
        ];

        // ── Title row ─────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', '📅 Export Disponibilités — ' . $subtitle . ' — ' . date('d/m/Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4A2880']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ── Column headers ────────────────────────────────────────────────────
        $headers = ['A' => 'Jour', 'B' => 'Heure Début', 'C' => 'Heure Fin',
                    'D' => 'Durée (min)', 'E' => 'Cabinet', 'F' => 'Créneaux réservés', 'G' => 'Taux occupation'];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $sheet->getStyle('A2:G2')->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(26);

        // ── Data rows ─────────────────────────────────────────────────────────
        $row           = 3;
        $totalCreneaux = 0;
        $totalReserves = 0;

        foreach ($disponibilites as $dispo) {
            $jour       = self::JOURS[$dispo->getJour()] ?? 'Jour ' . $dispo->getJour();
            $heureDebut = $dispo->getHeureDebut()?->format('H:i') ?? '-';
            $heureFin   = $dispo->getHeureFin()?->format('H:i') ?? '-';
            $duree      = $dispo->getDureeConsultation();
            $cabinet    = $dispo->getCabinet();
            $cabinetLabel = $cabinet
                ? $cabinet->getVille() . ' — ' . $cabinet->getAdresse()
                : '-';

            // Count reserved creneaux for this disponibilite
            $creneaux        = $dispo->getCreneaux();
            $nbTotal         = count($creneaux);
            $nbReserves      = 0;
            foreach ($creneaux as $c) {
                if ($c->getStatut() === 'RESERVE') {
                    $nbReserves++;
                }
            }
            $tauxOccupation  = $nbTotal > 0 ? round($nbReserves / $nbTotal * 100) . '%' : '0%';
            $totalCreneaux  += $nbTotal;
            $totalReserves  += $nbReserves;

            $sheet->setCellValue('A' . $row, $jour);
            $sheet->setCellValue('B' . $row, $heureDebut);
            $sheet->setCellValue('C' . $row, $heureFin);
            $sheet->setCellValue('D' . $row, $duree);
            $sheet->setCellValue('E' . $row, $cabinetLabel);
            $sheet->setCellValue('F' . $row, $nbReserves . ' / ' . $nbTotal);
            $sheet->setCellValue('G' . $row, $tauxOccupation);

            // Row style — alternate + color by occupation
            $bgColor = ($row % 2 === 0) ? 'FFF9F5FF' : 'FFFFFFFF';
            if ($nbTotal > 0 && $nbReserves === $nbTotal) {
                $bgColor = 'FFFEE2E2'; // full — red tint
            } elseif ($nbReserves === 0) {
                $bgColor = 'FFD1FAE5'; // empty — green tint
            }

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);

            $row++;
        }

        // ── Summary row ───────────────────────────────────────────────────────
        $summaryRow = $row + 1;
        $tauxGlobal = $totalCreneaux > 0 ? round($totalReserves / $totalCreneaux * 100) . '%' : '0%';

        $sheet->setCellValue('A' . $summaryRow, 'TOTAL');
        $sheet->setCellValue('B' . $summaryRow, count($disponibilites) . ' disponibilités');
        $sheet->setCellValue('F' . $summaryRow, $totalReserves . ' / ' . $totalCreneaux);
        $sheet->setCellValue('G' . $summaryRow, $tauxGlobal);
        $sheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEDE9FE']],
        ]);

        // ── Legend ────────────────────────────────────────────────────────────
        $legendRow = $summaryRow + 2;
        $sheet->setCellValue('A' . $legendRow, 'Légende :');
        $sheet->setCellValue('B' . $legendRow, 'Vert = aucun créneau réservé');
        $sheet->setCellValue('D' . $legendRow, 'Rouge = tous les créneaux réservés');
        $sheet->getStyle('A' . $legendRow . ':G' . $legendRow)
            ->getFont()->setItalic(true)->setSize(9);

        // ── Column widths ─────────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(16);

        // ── Metadata ──────────────────────────────────────────────────────────
        $spreadsheet->getProperties()
            ->setCreator('Cabinet Psychologie')
            ->setTitle('Export Disponibilités')
            ->setDescription('Généré automatiquement le ' . date('d/m/Y H:i'));

        return $spreadsheet;
    }
}
