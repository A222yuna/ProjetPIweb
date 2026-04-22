<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    public function __construct(
        private \App\Repository\UserRepository $userRepository,
        private \App\Repository\ProgrammeBienEtreRepository $programmeRepository,
        private \App\Repository\ActiviteProgrammeRepository $activiteRepository,
        private \App\Repository\AvisRepository $avisRepository,
    ) {}

    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        // Stats pour les cartes
        $totalUsers = $this->userRepository->count([]);
        $totalProgrammes = $this->programmeRepository->count([]);
        $totalActivites = $this->activiteRepository->count([]);
        $totalAvis = $this->avisRepository->count([]);

        $avgActivitiesPerProgramme = $totalProgrammes > 0
            ? round($totalActivites / $totalProgrammes, 1)
            : 0;

        $programmesWithNoActivities = (int) $this->programmeRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('SIZE(p.activites) = 0')
            ->getQuery()
            ->getSingleScalarResult();

        $avgAvisScore = (float) ($this->avisRepository->createQueryBuilder('av')
            ->select('AVG(av.note)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        // Données pour le Pie Chart (Niveau de difficulté des programmes)
        $difficultyStats = $this->programmeRepository->createQueryBuilder('p')
            ->select('p.niveauDifficulte, COUNT(p.id) as count')
            ->groupBy('p.niveauDifficulte')
            ->getQuery()
            ->getResult();

        // Données pour le Bar Chart (Types d'activités)
        $typeStats = $this->activiteRepository->createQueryBuilder('a')
            ->select('a.typeActivite, COUNT(a.id) as count')
            ->groupBy('a.typeActivite')
            ->getQuery()
            ->getResult();

        // Données pour le Line Chart (Inscriptions utilisateurs par mois - simulation si pas assez de données)
        $userGrowth = $this->userRepository->createQueryBuilder('u')
            ->select('SUBSTRING(u.dateInscription, 1, 7) as month, COUNT(u.id) as count')
            ->where('u.dateInscription IS NOT NULL')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        $avisDistribution = $this->avisRepository->createQueryBuilder('av')
            ->select('av.note AS note, COUNT(av.id) AS count')
            ->groupBy('av.note')
            ->orderBy('av.note', 'ASC')
            ->getQuery()
            ->getResult();

        $activitiesByDay = $this->activiteRepository->createQueryBuilder('a')
            ->select('a.jour AS day, COUNT(a.id) AS count')
            ->groupBy('a.jour')
            ->orderBy('a.jour', 'ASC')
            ->getQuery()
            ->getResult();

        $topProgrammesByAvis = $this->programmeRepository->createQueryBuilder('p')
            ->select('p.nom AS name, COUNT(av.id) AS count')
            ->leftJoin('p.avis', 'av')
            ->groupBy('p.id')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $response = $this->render('dashboard/admin.html.twig', [
            'totalUsers' => $totalUsers,
            'totalProgrammes' => $totalProgrammes,
            'totalActivites' => $totalActivites,
            'totalAvis' => $totalAvis,
            'avgActivitiesPerProgramme' => $avgActivitiesPerProgramme,
            'programmesWithNoActivities' => $programmesWithNoActivities,
            'avgAvisScore' => round($avgAvisScore, 1),
            'difficultyStats' => $difficultyStats,
            'typeStats' => $typeStats,
            'userGrowth' => $userGrowth,
            'avisDistribution' => $avisDistribution,
            'activitiesByDay' => $activitiesByDay,
            'topProgrammesByAvis' => $topProgrammesByAvis,
        ]);

        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store', true);

        return $response;
    }

    #[Route('/psychologue/dashboard', name: 'app_psychologue_dashboard')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psychologue(): Response
    {
        return $this->render('dashboard/psychologue.html.twig');
    }

    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]
    #[IsGranted('ROLE_PATIENT')]
    public function patient(): Response
    {
        return $this->render('dashboard/patient.html.twig');
    }
}
