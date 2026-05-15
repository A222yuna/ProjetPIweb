<?php

namespace App\Twig;

use App\Service\WeatherService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WeatherExtension extends AbstractExtension
{
    public function __construct(
        private WeatherService $weatherService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_weather', [$this, 'getWeather']),
        ];
    }

    public function getWeather(?string $city = null): ?array
    {
        return $this->weatherService->getWeather($city);
    }
}
