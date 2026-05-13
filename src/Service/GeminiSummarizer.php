<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiSummarizer
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';
    private const SUMMARY_MAX_CHARS = 480;
    private const INPUT_MAX_CHARS = 3000; // truncate long posts before sending
    private const PREFERRED_MODEL_PATTERNS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
        'gemini-pro',
    ];

    private ?string $resolvedModelName = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $geminiApiKey = '',
    ) {
    }

    public function summarizePost(string $title, string $content): string
    {
        if ($this->geminiApiKey === '') {
            throw new \RuntimeException('La cle API Gemini est absente. Configurez GEMINI_API_KEY dans votre environnement.');
        }

        // Truncate very long posts so the model doesn't burn tokens on input
        $content = mb_strlen($content) > self::INPUT_MAX_CHARS
            ? mb_substr($content, 0, self::INPUT_MAX_CHARS) . '…'
            : $content;

        $basePrompt = <<<PROMPT
Fais un resume en francais du post suivant.
Regles:
- 2 phrases completes maximum
- ton neutre et bienveillant
- reformuler (pas copier-coller)
- pas de salutation, seulement le resume
- sois concis

Titre: {$title}
Texte: {$content}
PROMPT;

        $modelName = $this->resolveModelName();
        $attempts = [
            $basePrompt,
            $basePrompt . "\n\nImportant: donne exactement 2 phrases completes, sans couper la fin.",
        ];

        $lastData = null;
        foreach ($attempts as $prompt) {
            $data = $this->requestSummary($modelName, $prompt);
            $lastData = $data;
            $text = $this->extractText($data);
            if ($text === null || trim($text) === '') {
                continue;
            }

            $summary = $this->normalizeSummary(trim($text));
            if ($this->isAcceptableSummary($summary)) {
                return $summary;
            }

            $finishReason = $data['candidates'][0]['finishReason'] ?? null;
            if ($finishReason === 'MAX_TOKENS') {
                $salvaged = $this->salvageTruncatedSummary($summary);
                if ($this->isAcceptableSummary($salvaged)) {
                    return $salvaged;
                }
            }
        }

        $errors = [];
        $errorMessage = $this->extractErrorMessage($lastData ?? []);
        if ($errorMessage !== null) {
            $errors[] = $errorMessage;
        }

        $finishReason = $lastData['candidates'][0]['finishReason'] ?? null;
        if (\is_string($finishReason) && $finishReason !== '') {
            $errors[] = 'Gemini finishReason: ' . $finishReason;
        }

        $blockReason = $lastData['promptFeedback']['blockReason'] ?? null;
        if (\is_string($blockReason) && $blockReason !== '') {
            $errors[] = 'Gemini blockReason: ' . $blockReason;
        }

        throw new \RuntimeException(
            'Echec Gemini (' . $modelName . '). ' . ($errors !== [] ? implode(' | ', array_unique($errors)) : 'Aucune sortie exploitable retournee.')
        );
    }

    private function requestSummary(string $modelName, string $prompt): array
    {
        $response = $this->httpClient->request('POST', self::API_BASE . '/' . $modelName . ':generateContent?key=' . urlencode($this->geminiApiKey), [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 1024,
                ],
            ],
        ]);

        return $response->toArray(false);
    }

    private function extractText(array $payload): ?string
    {
        $parts = $payload['candidates'][0]['content']['parts'] ?? null;
        if (!\is_array($parts)) {
            return null;
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (isset($part['text']) && \is_string($part['text'])) {
                $chunks[] = $part['text'];
            }
        }

        return $chunks !== [] ? implode("\n", $chunks) : null;
    }

    private function extractErrorMessage(array $payload): ?string
    {
        $message = $payload['error']['message'] ?? null;
        return \is_string($message) && $message !== '' ? $message : null;
    }

    private function resolveModelName(): string
    {
        if ($this->resolvedModelName !== null) {
            return $this->resolvedModelName;
        }

        $response = $this->httpClient->request('GET', self::API_BASE . '/models?key=' . urlencode($this->geminiApiKey));
        $data = $response->toArray(false);
        $models = $data['models'] ?? null;

        if (!\is_array($models) || $models === []) {
            throw new \RuntimeException('Aucun modele Gemini retourne par ListModels.');
        }

        $supported = [];
        foreach ($models as $model) {
            $name = $model['name'] ?? null;
            $methods = $model['supportedGenerationMethods'] ?? [];
            if (!\is_string($name) || !\is_array($methods)) {
                continue;
            }
            if (!\in_array('generateContent', $methods, true)) {
                continue;
            }
            if (!str_contains($name, 'gemini')) {
                continue;
            }
            $supported[] = $name;
        }

        if ($supported === []) {
            throw new \RuntimeException('Aucun modele Gemini compatible generateContent trouve pour cette cle API.');
        }

        foreach (self::PREFERRED_MODEL_PATTERNS as $pattern) {
            foreach ($supported as $name) {
                if (str_contains($name, $pattern)) {
                    $this->resolvedModelName = $name;
                    return $name;
                }
            }
        }

        $this->resolvedModelName = $supported[0];
        return $supported[0];
    }

    private function normalizeSummary(string $text): string
    {
        $compact = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        if ($compact === '') {
            return 'Resume indisponible pour le moment.';
        }

        // If output is cut, keep only full sentences.
        if (!\in_array(mb_substr($compact, -1), ['.', '!', '?'], true)) {
            $compact = preg_replace('/^(.+[.!?]).*$/u', '$1', $compact) ?? $compact;
        }

        if (mb_strlen($compact) <= self::SUMMARY_MAX_CHARS) {
            return $compact;
        }

        return rtrim(mb_substr($compact, 0, self::SUMMARY_MAX_CHARS - 3)) . '...';
    }

    private function salvageTruncatedSummary(string $text): string
    {
        $compact = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        if ($compact === '') {
            return '';
        }

        preg_match_all('/[^.!?]+[.!?]/u', $compact, $matches);
        $sentences = $matches[0] ?? [];
        if (\is_array($sentences) && $sentences !== []) {
            $picked = implode(' ', array_slice(array_map('trim', $sentences), 0, 2));
            return $this->normalizeSummary($picked);
        }

        $short = mb_substr($compact, 0, 220);
        return rtrim($short, " \t\n\r\0\x0B,;:") . '.';
    }

    private function isAcceptableSummary(string $text): bool
    {
        if (mb_strlen($text) < 45) {
            return false;
        }

        $lastChar = mb_substr($text, -1);
        if (!\in_array($lastChar, ['.', '!', '?'], true)) {
            return false;
        }

        $sentenceCount = preg_match_all('/[.!?](?=\s|$)/u', $text);
        if (!\is_int($sentenceCount) || $sentenceCount < 1) {
            return false;
        }

        return true;
    }
}
