<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ForumNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ForumNotificationController extends AbstractController
{
    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Connexion requise.');
        }
        return $user;
    }

    #[Route('/forum/notifications', name: 'app_forum_notifications', methods: ['GET'])]
    public function index(ForumNotificationRepository $repo): Response
    {
        $user = $this->requireUser();
        $notifications = $repo->findForUser($user);
        $repo->markAllReadForUser($user);

        return $this->render('forum/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }
}
