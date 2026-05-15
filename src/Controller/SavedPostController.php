<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class SavedPostController extends AbstractController
{
    private function requireForumUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Vous devez être connecté.');
        }

        if (!\in_array($user->getRole(), ['Patient', 'Psychologue', 'Admin'], true)) {
            throw new AccessDeniedException('Accès interdit.');
        }

        return $user;
    }

    #[Route('/saved-posts', name: 'app_saved_posts', methods: ['GET'])]
    public function list(): Response
    {
        $user = $this->requireForumUser();

        return $this->render('post/saved.html.twig', [
            'saved' => $user->getSavedPosts(),
        ]);
    }

    #[Route('/consultation/{id}/save', name: 'app_post_save_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request, PostRepository $posts, EntityManagerInterface $em): Response
    {
        $user = $this->requireForumUser();
        $post = $posts->find($id);
        if (!$post instanceof Post) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('save_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        if ($user->hasSavedPost($post)) {
            $user->removeSavedPost($post);
            $this->addFlash('success', 'Publication retirée de vos favoris.');
        } else {
            $user->addSavedPost($post);
            $this->addFlash('success', 'Publication enregistrée dans vos favoris.');
        }

        $em->flush();

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }
}
