<?php

namespace App\Controller\Api;

use App\Repository\CabinetRepository;
use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CabinetMapApiController extends AbstractController
{
    /** Fallback city-center coordinates for Tunisia (used when geocoding fails) */
    private const CITY_FALLBACKS = [
        'tunis'    => ['lat' => 36.8065, 'lng' => 10.1815],
        'sfax'     => ['lat' => 34.7406, 'lng' => 10.7603],
        'sousse'   => ['lat' => 35.8256, 'lng' => 10.6369],
        'gabes'    => ['lat' => 33.8814, 'lng' => 10.0982],
        'bizerte'  => ['lat' => 37.2746, 'lng' => 9.8739],
        'nabeul'   => ['lat' => 36.4561, 'lng' => 10.7376],
        'monastir' => ['lat' => 35.7643, 'lng' => 10.8113],
        'kairouan' => ['lat' => 35.6781, 'lng' => 10.0963],
        'mednine'  => ['lat' => 33.3549, 'lng' => 10.5055],
        'default'  => ['lat' => 33.8869, 'lng' => 9.5375],
    ];

    #[Route('/api/cabinets/map-data', name: 'api_cabinets_map_data', methods: ['GET'])]
    public function getMapData(
        CabinetRepository $cabinetRepository,
        GeocodingService $geocodingService,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $cabinets = $cabinetRepository->findVisibleForPatients();
        $mapData = [];
        $shouldFlush = false;

        foreach ($cabinets as $cabinet) {
            // Geocode only if coordinates are missing (sleep(1) is inside GeocodingService)
            if (null === $cabinet->getLatitude() || null === $cabinet->getLongitude()) {
                $coords = $geocodingService->geocodeAddress(
                    (string) $cabinet->getAdresse(),
                    (string) $cabinet->getVille()
                );

                // Fall back to city center when Nominatim fails (rate-limit, unknown address, etc.)
                if (!$coords) {
                    $key = strtolower(trim((string) $cabinet->getVille()));
                    $coords = self::CITY_FALLBACKS[$key] ?? self::CITY_FALLBACKS['default'];
                }

                $cabinet->setLatitude($coords['lat']);
                $cabinet->setLongitude($coords['lng']);
                $shouldFlush = true;
            }

            $mapData[] = [
                'id'         => $cabinet->getId(),
                'adresse'    => $cabinet->getAdresse(),
                'ville'      => $cabinet->getVille(),
                'latitude'   => (float) $cabinet->getLatitude(),
                'longitude'  => (float) $cabinet->getLongitude(),
                'statut'     => $cabinet->isValide() ? 'Valide' : 'En attente',
                'detail_url' => $urlGenerator->generate('app_cabinet_front_show', ['id' => $cabinet->getId()]),
            ];
        }

        if ($shouldFlush) {
            $em->flush();
        }

        return $this->json([
            'success'  => true,
            'count'    => count($mapData),
            'cabinets' => $mapData,
        ]);
    }
}
