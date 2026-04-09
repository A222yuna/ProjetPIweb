<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_users_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users): Response
    {
        $role = $request->query->getString('role');
        $activeRaw = $request->query->get('active');
        $active = $activeRaw === null || $activeRaw === '' ? null : $activeRaw === '1';
        $page = max(1, $request->query->getInt('page', 1));

        $result = $users->findAdminPaginated($role !== '' ? $role : null, $active, $page, 15);

        return $this->render('admin/users/index.html.twig', [
            'users' => $result['items'],
            'role_filter' => $role,
            'active_filter' => $activeRaw,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / 15)),
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'app_admin_users_toggle_active', methods: ['POST'])]
    public function toggleActive(int $id, Request $request, UserRepository $users, EntityManagerInterface $em): Response
    {
        $user = $users->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('toggle_active_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        $user->setEstActif(!$user->isEstActif());
        $em->flush();
        $this->addFlash('success', 'Statut actif mis a jour.');

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/{id}/toggle-verified', name: 'app_admin_users_toggle_verified', methods: ['POST'])]
    public function toggleVerified(int $id, Request $request, UserRepository $users, EntityManagerInterface $em): Response
    {
        $user = $users->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('toggle_verified_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        $user->setEmailVerifie(!$user->isEmailVerifie());
        $em->flush();
        $this->addFlash('success', 'Verification email mise a jour.');

        return $this->redirectToRoute('app_admin_users_index');
    }
}
