<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private CacheInterface $cache;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        CacheInterface $cache,
        string $openweatherApiKey,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->apiKey = $openweatherApiKey;
        $this->logger = $logger;
    }

    public function getWeather(string $city): ?array
    {
        try {
            $cacheKey = 'weather_' . md5($city);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city) {
                $item->expiresAfter(1800); // 30 minutes

                $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                    'extra' => [
                        'curl' => [
                            113 => 1, // CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
                        ],
                    ],
                    'query' => [
                        'q' => $city,
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => 'fr'
                    ]
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    $error = $response->getContent(false);
                    $this->logger->error(sprintf('Weather API Error: Status %d, City: %s, Response: %s', $statusCode, $city, $error));
                    $item->expiresAfter(60); 
                    return null;
                }

                $data = $response->toArray();

                return [
                    'city' => $data['name'] ?? $city,
                    'temp' => round($data['main']['temp'] ?? 0),
                    'description' => $data['weather'][0]['description'] ?? 'Indisponible',
                    'icon' => $data['weather'][0]['icon'] ?? '01d',
                    'humidity' => $data['main']['humidity'] ?? 0,
                    'wind' => $data['wind']['speed'] ?? 0,
                ];
            });
        } catch (\Exception $e) {
            $this->logger->error('WeatherService Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
