<?php

namespace App\Controller\Admin;

use App\Repository\PostRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/modules/forum')]
#[IsGranted('ROLE_ADMIN')]
final class ForumAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_module_forum', methods: ['GET'])]
    public function listPosts(Request $request, PostRepository $posts): Response
    {
        $q = $request->query->getString('q');
        $cat = $request->query->getString('categorie');
        $sort = $request->query->getString('sort', 'recent');
        $page = max(1, $request->query->getInt('page', 1));

        $result = $posts->searchConsultationsPaginated($q, $cat, $page, 15, $sort);

        return $this->render('admin/forum/posts.html.twig', [
            'posts' => $result['items'],
            'q' => $q,
            'cat' => $cat,
            'sort' => $sort,
            'page' => $page,
            'total_pages' => (int) ceil($result['total'] / 15),
        ]);
    }

    #[Route('/comments', name: 'app_admin_forum_comments', methods: ['GET'])]
    public function listComments(Request $request, CommentaireRepository $comments): Response
    {
        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort', 'recent');
        $page = max(1, $request->query->getInt('page', 1));

        $result = $comments->findAdminPaginated($q, $page, 20, $sort);

        return $this->render('admin/forum/comments.html.twig', [
            'comments' => $result['items'],
            'q' => $q,
            'sort' => $sort,
            'page' => $page,
            'total_pages' => (int) ceil($result['total'] / 20),
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_admin_forum_post_delete', methods: ['POST'])]
    public function deletePost(int $id, Request $request, PostRepository $posts, EntityManagerInterface $em): Response
    {
        $post = $posts->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'La publication a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_module_forum');
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_forum_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, Request $request, CommentaireRepository $comments, EntityManagerInterface $em): Response
    {
        $comment = $comments->find($id);
        if (!$comment) {
            throw $this->createNotFoundException();
        }

        $postId = $comment->getPost()?->getId();

        if ($this->isCsrfTokenValid('delete_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');
        }

        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, '/consultation/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_admin_forum_comments');
    }
}
