<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiService
{
    private $client;
    private $apiKey;
    private $model = 'gemini-2.5-flash';

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

        return $this->callGemini($prompt);
    }

    /**
     * Provides personalized advice for a specific activity.
     */
    public function getActivityAdvice(string $activityTitle, string $description): string
    {
        $prompt = "En tant qu'expert en bien-être, donnez un conseil court et motivant (maximum 2 phrases) pour cette activité : '$activityTitle'. Description : $description";

        $response = $this->callGemini($prompt, false);
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? "Profitez de ce moment pour vous recentrer.";
    }

    private function callGemini(string $prompt, bool $isJson = true): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        if ($isJson) {
            $prompt .= "\nIMPORTANT: Retournez uniquement le JSON, sans texte superflu ni balises Markdown.";
        }

        $response = $this->client->request('POST', $url, [
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
            ]
        ]);

        if ($isJson) {
            return $response->toArray();
        }

        return $response->toArray();
    }
}
