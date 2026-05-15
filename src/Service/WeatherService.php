<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WeatherService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $weatherApiKey,
        private string $weatherCity
    ) {}

    public function getWeather(?string $city = null): ?array
    {
        $city = $city ?: $this->weatherCity;
        
        try {
            return $this->cache->get('weather_' . md5($city), function (ItemInterface $item) use ($city) {
                $item->expiresAfter(1800); // 30 minutes

                $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                    'query' => [
                        'q' => $city,
                        'appid' => $this->weatherApiKey,
                        'units' => 'metric',
                        'lang' => 'fr'
                    ]
                ]);

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $data = $response->toArray();

                return [
                    'city' => $data['name'],
                    'temp' => round($data['main']['temp']),
                    'description' => $data['weather'][0]['description'],
                    'icon' => $data['weather'][0]['icon'],
                    'humidity' => $data['main']['humidity'],
                    'wind' => round($data['wind']['speed'] * 3.6) // m/s to km/h
                ];
            });
        } catch (\Exception $e) {
            return null;
        }
    }
}
