<?php

namespace App\Service;

use App\Repository\UserRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;

class StatisticsService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getUserRoleChart(): PieChart
    {
        $data = $this->userRepository->createQueryBuilder('u')
            ->select('u.role, COUNT(u.id) as count')
            ->groupBy('u.role')
            ->getQuery()
            ->getResult();

        $chartData = [['Rôle', 'Nombre']];
        foreach ($data as $row) {
            $chartData[] = [$row['role'] ?: 'Sans rôle', (int) $row['count']];
        }

        $chart = new PieChart();
        $chart->getData()->setArrayToDataTable($chartData);
        $chart->getOptions()->setTitle('Utilisateurs par Rôle');
        $chart->getOptions()->setHeight(300);
        $chart->getOptions()->setPieHole(0);
        $chart->getOptions()->getLegend()->setPosition('bottom');

        return $chart;
    }

    public function getUserStatusChart(): PieChart
    {
        $data = $this->userRepository->createQueryBuilder('u')
            ->select('u.estActif, COUNT(u.id) as count')
            ->groupBy('u.estActif')
            ->getQuery()
            ->getResult();

        $chartData = [['Statut', 'Nombre']];
        foreach ($data as $row) {
            $chartData[] = [$row['estActif'] ? 'Actif' : 'Bloqué / Inactif', (int) $row['count']];
        }

        $chart = new PieChart();
        $chart->getData()->setArrayToDataTable($chartData);
        $chart->getOptions()->setTitle('Statut des Comptes');
        $chart->getOptions()->setHeight(300);
        $chart->getOptions()->setPieHole(0.4); // Doughnut effect
        $chart->getOptions()->getLegend()->setPosition('bottom');
        $chart->getOptions()->setColors(['#1cc88a', '#e74a3b']);

        return $chart;
    }
}
