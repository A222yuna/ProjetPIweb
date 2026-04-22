<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function geocodeAddress(string $adresse, string $ville): ?array
    {
        // Nominatim enforces 1 request/second — always wait before calling
        sleep(1);

        try {
            $fullAddress = sprintf('%s, %s, Tunisia', $adresse, $ville);
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $fullAddress,
                    'format' => 'json',
                    'limit' => 1,
                ],
                'headers' => [
                    'User-Agent' => 'PsychologyCabinetApp/1.0',
                ],
            ]);

            $data = $response->toArray();
            if (!empty($data)) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                ];
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
