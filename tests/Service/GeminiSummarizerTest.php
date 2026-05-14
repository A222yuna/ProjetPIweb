<?php

namespace App\Tests\Service;

use App\Service\GeminiSummarizer;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GeminiSummarizerTest extends TestCase
{
    private \ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new \ReflectionClass(GeminiSummarizer::class);
    }

    /** Call a private method by name */
    private function callPrivate(GeminiSummarizer $obj, string $method, mixed ...$args): mixed
    {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($obj, ...$args);
    }

    private function makeSummarizer(string $apiKey = 'fake-key'): GeminiSummarizer
    {
        return new GeminiSummarizer($this->createMock(HttpClientInterface::class), $apiKey);
    }

    // ── API key validation ────────────────────────────────────────────────────

    public function testThrowsWhenApiKeyIsEmpty(): void
    {
        $summarizer = $this->makeSummarizer('');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/GEMINI_API_KEY/');
        $summarizer->summarizePost('Titre', 'Contenu du post.');
    }

    // ── Successful summary ────────────────────────────────────────────────────

    public function testReturnsSummaryOnSuccess(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $modelsResponse = $this->createMock(ResponseInterface::class);
        $modelsResponse->method('toArray')->willReturn([
            'models' => [[
                'name' => 'models/gemini-2.5-flash',
                'supportedGenerationMethods' => ['generateContent'],
            ]],
        ]);

        $summaryResponse = $this->createMock(ResponseInterface::class);
        $summaryResponse->method('toArray')->willReturn([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Ceci est un résumé valide du post. Il contient deux phrases complètes.']]],
                'finishReason' => 'STOP',
            ]],
        ]);

        $httpClient->method('request')
            ->willReturnOnConsecutiveCalls($modelsResponse, $summaryResponse);

        $summarizer = new GeminiSummarizer($httpClient, 'fake-api-key');
        $result = $summarizer->summarizePost('Titre test', 'Contenu du post de test.');

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('résumé', $result);
    }

    // ── Input truncation ──────────────────────────────────────────────────────

    public function testLongContentDoesNotCrashService(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $modelsResponse = $this->createMock(ResponseInterface::class);
        $modelsResponse->method('toArray')->willReturn([
            'models' => [[
                'name' => 'models/gemini-2.5-flash',
                'supportedGenerationMethods' => ['generateContent'],
            ]],
        ]);

        $summaryResponse = $this->createMock(ResponseInterface::class);
        $summaryResponse->method('toArray')->willReturn([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Ce post traite d\'un sujet important et détaillé. L\'auteur partage son expérience de manière claire.']]],
                'finishReason' => 'STOP',
            ]],
        ]);

        $httpClient->method('request')
            ->willReturnOnConsecutiveCalls($modelsResponse, $summaryResponse, $summaryResponse);

        $summarizer = new GeminiSummarizer($httpClient, 'fake-key');
        $result = $summarizer->summarizePost('Titre', str_repeat('a', 5000));

        $this->assertNotEmpty($result);
    }

    // ── API error handling ────────────────────────────────────────────────────

    public function testThrowsWhenNoModelsReturned(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $modelsResponse = $this->createMock(ResponseInterface::class);
        $modelsResponse->method('toArray')->willReturn(['models' => []]);

        $httpClient->method('request')->willReturn($modelsResponse);

        $summarizer = new GeminiSummarizer($httpClient, 'fake-key');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Aucun modele Gemini/');
        $summarizer->summarizePost('Titre', 'Contenu.');
    }

    // ── isAcceptableSummary (private) ─────────────────────────────────────────

    public function testAcceptableWhenLongEnoughAndEndWithPeriod(): void
    {
        $s = $this->makeSummarizer();
        $text = 'Ce post parle d\'un sujet important pour la santé mentale. L\'auteur partage son vécu avec bienveillance.';
        $this->assertTrue($this->callPrivate($s, 'isAcceptableSummary', $text));
    }

    public function testNotAcceptableWhenTooShort(): void
    {
        $s = $this->makeSummarizer();
        $this->assertFalse($this->callPrivate($s, 'isAcceptableSummary', 'Trop court.'));
    }

    public function testNotAcceptableWhenNoEndPunctuation(): void
    {
        $s = $this->makeSummarizer();
        $text = 'Ce résumé est assez long mais ne se termine pas par un point';
        $this->assertFalse($this->callPrivate($s, 'isAcceptableSummary', $text));
    }

    // ── normalizeSummary (private) ────────────────────────────────────────────

    public function testNormalizeSummaryTrimsWhitespace(): void
    {
        $s = $this->makeSummarizer();
        $result = $this->callPrivate($s, 'normalizeSummary', '  Voici un résumé avec des espaces.  ');
        $this->assertEquals('Voici un résumé avec des espaces.', $result);
    }

    public function testNormalizeSummaryCollapsesMultipleSpaces(): void
    {
        $s = $this->makeSummarizer();
        $result = $this->callPrivate($s, 'normalizeSummary', 'Voici   un   résumé.');
        $this->assertStringNotContainsString('  ', $result);
    }

    public function testNormalizeSummaryReturnsPlaceholderForEmptyText(): void
    {
        $s = $this->makeSummarizer();
        $result = $this->callPrivate($s, 'normalizeSummary', '');
        $this->assertEquals('Resume indisponible pour le moment.', $result);
    }

    public function testNormalizeSummaryTruncatesAtMaxChars(): void
    {
        $s = $this->makeSummarizer();
        $longText = str_repeat('a', 200) . '. ' . str_repeat('b', 300) . '.';
        $result = $this->callPrivate($s, 'normalizeSummary', $longText);
        $this->assertLessThanOrEqual(480, mb_strlen($result));
    }
}
