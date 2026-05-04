<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $geminiApiKey = ''
    ) {
        $this->apiKey = $geminiApiKey;
    }

    public function generatePsychologistReport(string $presentation): string
    {
        if (empty($this->apiKey) || str_contains($this->apiKey, 'votre_clé')) {
            return "Erreur : La clé API n'est pas configurée dans le fichier .env.";
        }

        // Utilisation de la version la plus récente du modèle gemini-1.5-flash sur v1
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash-latest:generateContent?key=" . $this->apiKey;

        $prompt = "Tu es un assistant expert en recrutement de psychologues.
        Analyse la présentation suivante et fournis un rapport structuré en français (Points forts, Ton, Recommandation : approuver ou rejeter) :

        \"$presentation\"";

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $errorData = $response->toArray(false);
                $message = $errorData['error']['message'] ?? 'Erreur inconnue';
                return "Erreur API (Code $statusCode) : " . $message;
            }

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Erreur : Format de réponse de l'IA invalide.";
        } catch (\Exception $e) {
            return "Erreur de connexion à l'IA : " . $e->getMessage();
        }
    }
}
