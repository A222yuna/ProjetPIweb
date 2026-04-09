<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Form\PostConsultationFormType;
use App\Repository\CommentaireRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;

final class ConsultationController extends AbstractController
{
    private function requireForumRole(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Connexion requise.');
        }
        if (!\in_array($user->getRole(), ['Patient', 'Psychologue'], true)) {
            throw new AccessDeniedException('Acces reserve aux patients et psychologues.');
        }

        return $user;
    }

    #[Route('/posts', name: 'app_post_index')]
    public function redirectLegacyPosts(): RedirectResponse
    {
        return $this->redirectToRoute('app_consultation', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/posts/{id}', name: 'app_post_show_legacy', requirements: ['id' => '\d+'])]
    public function redirectLegacyPostShow(int $id): RedirectResponse
    {
        return $this->redirectToRoute('app_post_show', ['id' => $id], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/consultation/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->requireForumRole();
        $categorySuggestions = PostConsultationFormType::getCategoryChoices();

        $post = new Post();
        $post->setAuteur($user);
        $post->setAuteurRole($user->getRole());

        $form = $this->createForm(PostConsultationFormType::class, $post, [
            'category_choices' => $categorySuggestions,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuteur($user);
            $post->setAuteurRole($user->getRole());
            $post->setNbLikes(0);
            $post->setDate(new \DateTime());
            $em->persist($post);
            $em->flush();
            $this->addFlash('success', 'Votre publication a été enregistrée sur le forum.');

            return $this->redirectToRoute('app_consultation');
        }

        return $this->render('consultation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/consultation', name: 'app_consultation', methods: ['GET'])]
    public function index(
        Request $request,
        PostRepository $posts,
    ): Response {
        $this->requireForumRole();
        $q = $request->query->getString('q');
        $filterCategorie = $request->query->getString('categorie');
        $page = max(1, $request->query->getInt('page', 1));

        $categorySuggestions = PostConsultationFormType::getCategoryChoices();

        $result = $posts->searchConsultationsPaginated(
            $q !== '' ? $q : null,
            $filterCategorie !== '' ? $filterCategorie : null,
            $page,
            6
        );
        $totalPages = max(1, (int) ceil($result['total'] / 6));

        return $this->render('consultation/index.html.twig', [
            'posts' => $result['items'],
            'q' => $q,
            'filter_categorie' => $filterCategorie,
            'category_suggestions' => $categorySuggestions,
            'page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/consultation/{id}', name: 'app_post_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        Request $request,
        PostRepository $posts,
        CommentaireRepository $commentaires,
        EntityManagerInterface $em,
    ): Response
    {
        $user = $this->requireForumRole();
        $post = $posts->findOneWithComments($id);
        if (!$post) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $comment = new Commentaire();
        $comment->setPost($post);
        $comment->setAuteur($user);
        $comment->setAuteurRole($user->getRole());

        $replyTo = $request->query->getInt('reply_to', 0);
        if ($replyTo > 0) {
            $parent = $commentaires->find($replyTo);
            if ($parent && $parent->getPost()?->getId() === $post->getId()) {
                $comment->setParent($parent);
            }
        }

        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuteur($user);
            $comment->setAuteurRole($user->getRole());
            $comment->setNbLikes(0);
            $comment->setDate(new \DateTime());
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Votre commentaire a bien été ajouté.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $form,
            'commentFormOpen' => $form->isSubmitted() && !$form->isValid(),
            'replyTarget' => $comment->getParent(),
        ]);
    }

    #[Route('/consultation/{id}/like', name: 'app_post_like', methods: ['GET'])]
    public function likePost(int $id, Request $request, PostRepository $posts, EntityManagerInterface $em): RedirectResponse
    {
        $this->requireForumRole();
        $post = $posts->find($id);
        if ($post) {
            $session = $request->getSession();
            $likedPosts = $session->get('liked_posts', []);

            if (!in_array($id, $likedPosts)) {
                $post->setNbLikes($post->getNbLikes() + 1);
                $likedPosts[] = $id;
                $session->set('liked_posts', $likedPosts);
                $em->flush();
                $this->addFlash('success', 'Vous aimez cette publication !');
            } else {
                $this->addFlash('warning', 'Vous avez déjà aimé cette publication.');
            }
        }

        $referer = $request->headers->get('referer');
        if ($referer !== null) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_post_show', ['id' => $id]);
    }

    #[Route('/consultation/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        PostRepository $posts,
        EntityManagerInterface $em
    ): Response {
        $user = $this->requireForumRole();
        $post = $posts->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        if ($post->getAuteur()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Vous n\'êtes pas l\'auteur de cette publication.');
        }

        $categorySuggestions = PostConsultationFormType::getCategoryChoices();
        $form = $this->createForm(PostConsultationFormType::class, $post, [
            'category_choices' => $categorySuggestions,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre publication a été modifiée.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('consultation/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/consultation/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        PostRepository $posts,
        EntityManagerInterface $em
    ): RedirectResponse {
        $user = $this->requireForumRole();
        $post = $posts->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        if ($post->getAuteur()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Vous n\'êtes pas l\'auteur de cette publication.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Votre publication a été supprimée.');
        }

        return $this->redirectToRoute('app_consultation');
    }

    #[Route('/consultation/commentaire/{id}/like', name: 'app_comment_like', methods: ['GET'])]
    public function likeComment(int $id, Request $request, CommentaireRepository $commentaires, EntityManagerInterface $em): RedirectResponse
    {
        $this->requireForumRole();
        $comment = $commentaires->find($id);
        if ($comment) {
            $comment->setNbLikes($comment->getNbLikes() + 1);
            $em->flush();
        }

        $referer = $request->headers->get('referer');
        if ($referer !== null) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_post_show', ['id' => $comment?->getPost()?->getId() ?? 0]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig');
    }
}
