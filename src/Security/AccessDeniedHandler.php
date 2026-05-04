<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly TokenStorageInterface $tokenStorage,
    )
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): RedirectResponse
    {
        $roles = $this->tokenStorage->getToken()?->getRoleNames() ?? [];
        $targetRoute = match (true) {
            \in_array('ROLE_ADMIN', $roles, true) => 'app_admin_dashboard',
            \in_array('ROLE_PSYCHOLOGUE', $roles, true) => 'app_psychologue_dashboard',
            \in_array('ROLE_PATIENT', $roles, true) => 'app_patient_dashboard',
            default => 'app_login',
        };

        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('warning', 'Accès refusé pour cette page.');
            }
        }

        return new RedirectResponse($this->router->generate($targetRoute));
    }
}

