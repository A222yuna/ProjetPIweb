<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiService
{
    private const GEMINI_MODEL = 'gemini-2.5-flash';
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(
        HttpClientInterface $client,
        #[Autowire('%env(GEMINI_API_KEY)%')] string $apiKey
    ) {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Generates a full well-being program based on a theme and duration.
     */
    public function generateProgram(string $theme, int $days): array
    {
        $prompt = <<<PROMPT
Vous êtes un expert en psychologie et bien-être. 
Générez un programme de bien-être complet pour le thème suivant : "$theme" sur une durée de $days jours.
Répondez UNIQUEMENT au format JSON avec la structure suivante :
{
  "nom": "Titre accrocheur du programme",
  "objectif": "Objectif global du programme",
  "niveauDifficulte": "débutant/intermédiaire/avancé",
  "activites": [
    {
      "jour": 1,
      "titre": "Titre de l'activité",
      "description": "Description détaillée de l'activité",
      "heureDebut": "HH:MM",
      "dureeMinutes": 30,
      "typeActivite": "Méditation/Exercice/Réflexion/etc."
    }
  ]
}
Assurez-vous de générer au moins 1 activité par jour pour la durée demandée.
PROMPT;

        $response = $this->callGemini($prompt, true);
        $text = $this->extractText($response);
        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Réponse IA invalide: JSON non conforme.');
        }

        return $decoded;
    }

    /**
     * Provides personalized advice for a specific activity.
     */
    public function getActivityAdvice(string $activityTitle, string $description): string
    {
        $prompt = "En tant qu'expert en bien-être, donnez un conseil court et motivant (maximum 2 phrases) pour cette activité : '$activityTitle'. Description : $description";

        $response = $this->callGemini($prompt, false);
        $text = trim($this->extractText($response));

        return $text !== '' ? $text : "Profitez de ce moment pour vous recentrer.";
    }

    private function callGemini(string $prompt, bool $isJson = true): array
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            self::GEMINI_MODEL,
            $this->apiKey
        );

        if ($isJson) {
            $prompt .= "\nIMPORTANT: Retournez uniquement le JSON, sans texte superflu ni balises Markdown.";
        }

        $maxAttempts = 6;
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->client->request('POST', $url, [
                    'timeout' => 120,
                    // Local Windows SSL chain issue workaround.
                    'verify_peer' => false,
                    'verify_host' => false,
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
                        ],
                        'generationConfig' => [
                            'responseMimeType' => $isJson ? 'application/json' : 'text/plain'
                        ]
                    ],
                ]);

                // Force status code check (Symfony lazy-loads responses)
                $statusCode = $response->getStatusCode();

                if (in_array($statusCode, [401, 403], true)) {
                    throw new \RuntimeException(sprintf(
                        'Gemini access denied (HTTP %d): verify API key permissions and model access (%s).',
                        $statusCode,
                        self::GEMINI_MODEL
                    ));
                }

                return $response->toArray();

            } catch (\Throwable $e) {
                $lastError = $e;
                $msg = $e->getMessage();

                // Don't retry auth errors
                if (str_contains($msg, '401') || str_contains($msg, '403')) {
                    throw $e;
                }

                // Already used all attempts
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                // 429 = rate limited → wait a full 60s for the quota to reset
                if (str_contains($msg, '429')) {
                    sleep(60);
                    continue;
                }

                // 500/502/503/504 = server overloaded → shorter wait
                sleep(min(5 * $attempt, 20));
            }
        }

        if ($lastError instanceof \Throwable) {
            throw $lastError;
        }

        throw new \RuntimeException('Erreur temporaire Gemini, veuillez réessayer.');
    }

    private function extractText(array $response): string
    {
        // Gemini API response format: candidates[0].content.parts[0].text
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }

        return '';
    }
}
