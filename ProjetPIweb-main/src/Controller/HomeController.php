<?php

namespace App\Controller;

use App\Repository\CabinetRepository;
use App\Repository\PostRepository;
use App\Repository\ProgrammeBienEtreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        UserRepository $users,
        PostRepository $posts,
        CabinetRepository $cabinets,
        ProgrammeBienEtreRepository $programmes,
        EntityManagerInterface $entityManager,
    ): Response {
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        $hasUsers = $schemaManager->tablesExist(['users']);
        $hasPosts = $schemaManager->tablesExist(['post']);
        $hasCabinets = $schemaManager->tablesExist(['cabinet']);
        $hasProgrammes = $schemaManager->tablesExist(['programme_bien_etre']);

        return $this->render('home/index.html.twig', [
            'userCount' => $hasUsers ? $users->count([]) : 0,
            'postCount' => $hasPosts ? $posts->count([]) : 0,
            'cabinetCount' => $hasCabinets ? $cabinets->count([]) : 0,
            'programmeCount' => $hasProgrammes ? $programmes->count([]) : 0,
            'recentPosts' => $hasPosts ? $posts->findRecent() : [],
        ]);
    }
}
