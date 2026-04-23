<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications', methods: ['GET'])]
    public function index(NotificationRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $notifications = $repo->findByUser($user);
        // Mark all as read when viewing the page
        $repo->markAllAsRead($user);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/api/notifications/count', name: 'api_notif_count', methods: ['GET'])]
    public function count(NotificationRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['count' => 0]);
        }
        return $this->json(['count' => $repo->countUnread($user)]);
    }

    #[Route('/api/notifications/latest', name: 'api_notif_latest', methods: ['GET'])]
    public function latest(NotificationRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['notifications' => [], 'total_unread' => 0]);
        }

        $all  = $repo->findUnreadByUser($user);
        $data = array_map(fn($n) => [
            'id'         => $n->getId(),
            'type'       => $n->getType(),
            'title'      => $n->getTitle(),
            'message'    => $n->getMessage(),
            'link'       => $n->getLink(),
            'created_at' => $n->getCreatedAt()->format('d/m H:i'),
        ], array_slice($all, 0, 5));

        return $this->json([
            'notifications' => $data,
            'total_unread'  => count($all),
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'app_notif_read', methods: ['POST'])]
    public function markRead(int $id, NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $n    = $repo->find($id);

        if ($n && $n->getRecipient() === $user) {
            $n->setIsRead(true);
            $em->flush();
        }

        return $this->json(['success' => true]);
    }

    #[Route('/notifications/read-all', name: 'app_notif_read_all', methods: ['POST'])]
    public function markAllRead(NotificationRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $repo->markAllAsRead($user);
        }
        return $this->json(['success' => true]);
    }
}
