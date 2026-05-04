<?php

namespace App\Controller\Admin;

use App\Service\GeminiService;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserAdminController extends AbstractController
{
    #[Route('/{id}/ai-report', name: 'app_admin_users_ai_report', methods: ['GET'])]
    public function aiReport(int $id, UserRepository $users, GeminiService $gemini): Response
    {
        $user = $users->find($id);
        if (!$user || $user->getRole() !== 'Psychologue') {
            throw $this->createNotFoundException("Psychologue non trouvé.");
        }

        $presentation = $user->getPresentation();
        if (empty($presentation)) {
            return $this->json(['report' => "Ce psychologue n'a pas encore rempli sa présentation."]);
        }

        $report = $gemini->generatePsychologistReport($presentation);

        return $this->json(['report' => $report]);
    }

    #[Route('/', name: 'app_admin_users_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users, PaginatorInterface $paginator): Response
    {
        $role = $request->query->getString('role');
        $activeRaw = $request->query->get('active');
        $active = $activeRaw === null || $activeRaw === '' ? null : $activeRaw === '1';
        $search = $request->query->get('q');
        $page = max(1, $request->query->getInt('page', 1));

        $query = $users->createAdminListQueryBuilder(
            $role !== '' ? $role : null,
            $active,
            $search ?: null
        );

        $pagination = $paginator->paginate(
            $query,
            $page,
            5
        );

        $viewData = [
            'users' => $pagination,
            'role_filter' => $role,
            'active_filter' => $activeRaw,
            'search_term' => $search,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/users/_results.html.twig', $viewData);
        }

        return $this->render('admin/users/index.html.twig', $viewData);
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

        $newActiveState = !$user->isEstActif();
        $user->setEstActif($newActiveState);

        // If admin re-activates an account, unlock it completely.
        if ($newActiveState) {
            $user->resetFailedAttempts();
            $user->setLockedAt(null);

            // Activating a psychologue also validates the account.
            if ($user->getRole() === 'Psychologue' && $user->getStatutValidation() !== 'approuve') {
                $user->setStatutValidation('approuve');
            }
        }

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

    #[Route('/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, UserRepository $users, EntityManagerInterface $em): Response
    {
        $user = $users->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete_user_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_admin_users_index');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof \App\Entity\User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('app_admin_users_index');
        }

        try {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprime avec succes.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Impossible de supprimer cet utilisateur (donnees liees).');
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/connexions', name: 'app_admin_users_connections', methods: ['GET'])]
    public function connections(Request $request, UserRepository $users): Response
    {
        $search = $request->query->get('q');
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 20;

        $result = $users->findAdminPaginated(null, null, $page, $perPage, $search ?: null);

        return $this->render('admin/users/connections.html.twig', [
            'users' => $result['items'],
            'search_term' => $search,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / $perPage)),
        ]);
    }
}
