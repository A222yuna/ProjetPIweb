<?php

namespace App\Service;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function exportUsersToCsv(): StreamedResponse
    {
        $users = $this->userRepository->findAll();

        $response = new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Column headers
            fputcsv($handle, ['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Statut', 'Date Inscription'], ';');

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->getId(),
                    $user->getNom(),
                    $user->getPrenom(),
                    $user->getEmail(),
                    $user->getRole(),
                    $user->isEstActif() ? 'Actif' : 'Bloqué',
                    $user->getDateInscription() ? $user->getDateInscription()->format('d/m/Y') : '-'
                ], ';');
            }

            fclose($handle);
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'export_utilisateurs_' . date('Y-m-d') . '.csv'
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
