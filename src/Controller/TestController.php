<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\NotificationService;

class TestController extends AbstractController
{
    #[Route('/test-notification', name: 'app_test_notification')]
    public function testNotification(NotificationService $notificationService): Response
    {
        $notificationService->addTestNotification();
        $this->addFlash('success', 'Test notification added!');
        
        return $this->redirectToRoute('app_home');
    }
}
