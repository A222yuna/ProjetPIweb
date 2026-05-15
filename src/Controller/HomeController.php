<?php

namespace App\Controller;

use App\Repository\CabinetRepository;
use App\Repository\PostRepository;
use App\Repository\ProgrammeBienEtreRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/{_locale}/', name: 'app_home_locale', requirements: ['_locale' => 'fr|en'])]
    #[Route('/{_locale}', name: 'app_home_locale_no_slash', requirements: ['_locale' => 'fr|en'])]
    #[Route('/', name: 'app_home')]
    public function index(
        UserRepository $users,
        PostRepository $posts,
        CabinetRepository $cabinets,
        ProgrammeBienEtreRepository $programmes,
    ): Response {
        return $this->render('home/index.html.twig', [
            'userCount' => $users->count([]),
            'postCount' => $posts->count([]),
            'cabinetCount' => $cabinets->count([]),
            'programmeCount' => $programmes->count([]),
            'recentPosts' => $posts->findRecent(),
        ]);
    }
}
