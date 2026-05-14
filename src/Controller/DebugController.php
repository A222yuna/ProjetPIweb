<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\Session;

class DebugController extends AbstractController
{
    #[Route('/debug-session', name: 'app_debug_session')]
    public function debugSession(Session $session): Response
    {
        // Test direct session storage
        $testData = [
            'id' => 'debug_test',
            'subject' => 'Debug Test',
            'content' => 'This is a direct session test',
            'importance' => 'low',
            'timestamp' => time(),
            'read' => false
        ];
        
        $allNotifications = $session->get('all_notifications', []);
        $allNotifications['test_user'] = [$testData];
        $session->set('all_notifications', $allNotifications);
        
        // Log what we stored
        error_log('DEBUG: Direct session test - stored: ' . json_encode($allNotifications));
        
        return new Response('Debug session test completed. Check logs.');
    }
    
    #[Route('/debug-check', name: 'app_debug_check')]
    public function debugCheck(Session $session): Response
    {
        $allNotifications = $session->get('all_notifications', []);
        $testUserNotifications = $allNotifications['test_user'] ?? [];
        
        error_log('DEBUG: Session check - test user notifications: ' . json_encode($testUserNotifications));
        
        return new Response('Test user has ' . count($testUserNotifications) . ' notifications');
    }
}
