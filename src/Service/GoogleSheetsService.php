<?php

namespace App\Service;

use App\Repository\UserRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleSheetsService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UserRepository $userRepository,
        private ParameterBagInterface $params
    ) {}

    /**
     * Export data to Google Sheets using the API directly.
     * Note: Requires a Google OAuth Access Token or a Service Account Token.
     */
    public function exportToGoogleSheets(string $spreadsheetId, string $accessToken): array
    {
        $users = $this->userRepository->findAll();
        
        $values = [
            ['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Statut', 'Date Inscription']
        ];

        foreach ($users as $user) {
            $values[] = [
                (string) $user->getId(),
                (string) $user->getNom(),
                (string) $user->getPrenom(),
                (string) $user->getEmail(),
                (string) $user->getRole(),
                $user->isEstActif() ? 'Actif' : 'Bloqué',
                $user->getDateInscription() ? $user->getDateInscription()->format('d/m/Y') : '-'
            ];
        }

        // Tente d'écrire dans 'Sheet1' ou 'Feuille 1'. 
        // Si ça échoue, vérifiez le nom de l'onglet en bas de votre Google Sheets.
        $range = 'A1'; // Utiliser juste A1 ciblera la première feuille par défaut
        $url = sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?valueInputOption=USER_ENTERED', $spreadsheetId, $range);

        try {
            $response = $this->httpClient->request('PUT', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'range' => $range,
                    'majorDimension' => 'ROWS',
                    'values' => $values
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode !== 200) {
                $errorMsg = $content['error']['message'] ?? 'Erreur inconnue';
                return ['error' => "Google API ($statusCode) : " . $errorMsg];
            }

            return $content;
        } catch (\Exception $e) {
            return [
                'error' => "Exception : " . $e->getMessage()
            ];
        }
    }
}
