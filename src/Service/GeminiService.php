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

        try {
            $response = $this->callGemini($prompt, true);
            $text = $this->extractText($response);
            $decoded = json_decode($text, true);

            if (!is_array($decoded)) {
                throw new \RuntimeException('Réponse IA invalide: JSON non conforme.');
            }

            return $decoded;
        } catch (\Throwable $e) {
            // Fallback content just like in the other project if API is down
            return [
                'nom' => "Programme de base ($theme)",
                'objectif' => "Découverte et détente",
                'niveauDifficulte' => "débutant",
                'activites' => [
                    [
                        'jour' => 1,
                        'titre' => "Introduction à l'activité",
                        'description' => "Le service IA est temporairement indisponible, mais vous pouvez commencer par cette activité de base.",
                        'heureDebut' => "09:00",
                        'dureeMinutes' => 30,
                        'typeActivite' => "Général"
                    ]
                ]
            ];
        }
    }

    /**
     * Provides personalized advice for a specific activity.
     */
    public function getActivityAdvice(string $activityTitle, string $description): string
    {
        $prompt = "En tant qu'expert en bien-être, donnez un conseil court et motivant (maximum 2 phrases) pour cette activité : '$activityTitle'. Description : $description";

        try {
            $response = $this->callGemini($prompt, false);
            $text = trim($this->extractText($response));

            return $text !== '' ? $text : "Profitez de ce moment pour vous recentrer.";
        } catch (\Throwable $e) {
            return "Profitez de ce moment pour vous recentrer. (Service IA temporairement indisponible)";
        }
    }

    /**
     * Génère une description détaillée et engageante pour une activité ou un programme.
     */
    public function generateDescription(string $topic): string
    {
        $prompt = "Vous êtes un expert en psychologie et bien-être. Rédigez une description professionnelle, motivante et détaillée (environ 3-4 phrases) pour le sujet ou l'activité suivante : '$topic'. La description doit donner envie aux patients de participer. Retournez UNIQUEMENT le texte de la description sans aucun titre ni mise en forme spéciale.";

        try {
            $response = $this->callGemini($prompt, false);
            $text = trim($this->extractText($response));

            return $text !== '' ? $text : "Une excellente activité pour améliorer votre bien-être au quotidien.";
        } catch (\Throwable $e) {
            return "Une excellente activité pour améliorer votre bien-être au quotidien. (Service IA temporairement indisponible)";
        }
    }

    private function callGemini(string $prompt, bool $isJson = true): array
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            self::GEMINI_MODEL
        );

        if ($isJson) {
            $prompt .= "\nIMPORTANT: Retournez uniquement le JSON, sans texte superflu ni balises Markdown.";
        }

        $maxAttempts = 3;
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->client->request('POST', $url, [
                    'timeout' => 20,
                    'verify_peer' => false,
                    'verify_host' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $this->apiKey,
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
                            'responseMimeType' => $isJson ? 'application/json' : 'text/plain',
                            'temperature' => 0.7,
                            'maxOutputTokens' => 800,
                        ]
                    ],
                ]);

                $statusCode = $response->getStatusCode();

                // If fallback needed
                if (\in_array($statusCode, [400, 401, 403], true)) {
                    $queryUrl = $url . '?key=' . urlencode($this->apiKey);
                    $response = $this->client->request('POST', $queryUrl, [
                        'timeout' => 20,
                        'verify_peer' => false,
                        'verify_host' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'contents' => [['parts' => [['text' => $prompt]]]],
                            'generationConfig' => [
                                'responseMimeType' => $isJson ? 'application/json' : 'text/plain'
                            ]
                        ]
                    ]);
                    $statusCode = $response->getStatusCode();
                }

                if ($statusCode === 200) {
                    return $response->toArray();
                }

                // If 5xx or 429, retry
                if (\in_array($statusCode, [429, 500, 502, 503, 504], true)) {
                    if ($attempt < $maxAttempts) {
                        usleep(1000000 * $attempt);
                        continue;
                    }
                }

                throw new \RuntimeException('Gemini API error HTTP ' . $statusCode);

            } catch (\Throwable $e) {
                $lastError = $e;
                
                // Only retry on network errors or 5xx, if we haven't maxed out
                if ($attempt >= $maxAttempts) {
                    break;
                }
                usleep(1000000 * $attempt);
            }
        }

        if ($lastError instanceof \Throwable) {
            throw clone $lastError;
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
