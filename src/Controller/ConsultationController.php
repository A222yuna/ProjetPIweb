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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ConsultationController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    private function filterBadWords(?string $text): string
    {
        if (!$text) return '';

        try {
            $response = $this->httpClient->request('GET', 'https://www.purgomalum.com/service/json', [
                'query' => ['text' => $text]
            ]);
            $data = $response->toArray();
            return $data['result'] ?? $text;
        } catch (\Exception $e) {
            // Fallback to original text if API fails
            return $text;
        }
    }

    private function requireForumRole(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Connexion requise.');
        }
        if (!\in_array($user->getRole(), ['Patient', 'Psychologue', 'Admin'], true)) {
            throw new AccessDeniedException('Acces reserve aux patients, psychologues et administrateurs.');
        }

        return $user;
    }

    private function handleFileUpload(Post $post, $form, SluggerInterface $slugger): void
    {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/posts',
                    $newFilename
                );
                $post->setImageUrl('/uploads/posts/' . $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'image.');
            }
        }
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
        SluggerInterface $slugger,
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
            $this->handleFileUpload($post, $form, $slugger);
            
            $post->setTitre($this->filterBadWords($post->getTitre()));
            $post->setContenu($this->filterBadWords($post->getContenu()));
            
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
        $sortBy = $request->query->getString('sort', 'recent');
        $page = max(1, $request->query->getInt('page', 1));

        $categorySuggestions = PostConsultationFormType::getCategoryChoices();

        $result = $posts->searchConsultationsPaginated(
            $q !== '' ? $q : null,
            $filterCategorie !== '' ? $filterCategorie : null,
            $page,
            6,
            $sortBy,
            false
        );
        $totalPages = max(1, (int) ceil($result['total'] / 6));

        return $this->render('consultation/index.html.twig', [
            'posts' => $result['items'],
            'q' => $q,
            'filter_categorie' => $filterCategorie,
            'sort_by' => $sortBy,
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
        $post = $posts->findOneWithComments($id, $this->isGranted('ROLE_ADMIN'));
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
            $comment->setContenu($this->filterBadWords($comment->getContenu()));
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

    #[Route('/consultation/{id}/like', name: 'app_post_like', methods: ['GET', 'POST'])]
    public function likePost(int $id, Request $request, PostRepository $posts, EntityManagerInterface $em): Response
    {
        $this->requireForumRole();
        $post = $posts->find($id);
        $liked = false;

        if ($post) {
            $session = $request->getSession();
            $likedPosts = $session->get('liked_posts', []);

            if (!in_array($id, $likedPosts)) {
                // Add like
                $post->setNbLikes($post->getNbLikes() + 1);
                $likedPosts[] = $id;
                $liked = true;
            } else {
                // Remove like (toggle off)
                $post->setNbLikes(max(0, $post->getNbLikes() - 1));
                $likedPosts = array_filter($likedPosts, fn($postId) => $postId !== $id);
                $liked = false;
            }

            $session->set('liked_posts', $likedPosts);
            $em->flush();
        }

        // Return JSON for AJAX requests
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode([
                'liked' => $liked,
                'nbLikes' => $post?->getNbLikes() ?? 0,
            ]), 200, ['Content-Type' => 'application/json']);
        }

        // Fallback to redirect for non-AJAX
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
        EntityManagerInterface $em,
        SluggerInterface $slugger,
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
            $this->handleFileUpload($post, $form, $slugger);
            
            $post->setTitre($this->filterBadWords($post->getTitre()));
            $post->setContenu($this->filterBadWords($post->getContenu()));
            
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

    #[Route('/consultation/commentaire/{id}/like', name: 'app_comment_like', methods: ['GET', 'POST'])]
    public function likeComment(int $id, Request $request, CommentaireRepository $commentaires, EntityManagerInterface $em): Response
    {
        $this->requireForumRole();
        $comment = $commentaires->find($id);
        $liked = false;

        if ($comment) {
            $session = $request->getSession();
            $likedComments = $session->get('liked_comments', []);

            if (!in_array($id, $likedComments)) {
                // Add like
                $comment->setNbLikes($comment->getNbLikes() + 1);
                $likedComments[] = $id;
                $liked = true;
            } else {
                // Remove like (toggle off)
                $comment->setNbLikes(max(0, $comment->getNbLikes() - 1));
                $likedComments = array_filter($likedComments, fn($commentId) => $commentId !== $id);
                $liked = false;
            }

            $session->set('liked_comments', $likedComments);
            $em->flush();
        }

        // Return JSON for AJAX requests
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode([
                'liked' => $liked,
                'nbLikes' => $comment?->getNbLikes() ?? 0,
            ]), 200, ['Content-Type' => 'application/json']);
        }

        // Fallback to redirect for non-AJAX
        $referer = $request->headers->get('referer');
        if ($referer !== null) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_post_show', ['id' => $comment?->getPost()?->getId() ?? 0]);
    }

    #[Route('/consultation/commentaire/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function editComment(
        int $id,
        Request $request,
        CommentaireRepository $commentaires,
        EntityManagerInterface $em
    ): Response {
        $user = $this->requireForumRole();
        $comment = $commentaires->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        if ($comment->getAuteur()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Vous n\'êtes pas l\'auteur de ce commentaire.');
        }

        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setContenu($this->filterBadWords($comment->getContenu()));
            $em->flush();
            $this->addFlash('success', 'Votre commentaire a été modifié.');

            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        return $this->render('post/comment_edit.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/consultation/commentaire/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(
        int $id,
        Request $request,
        CommentaireRepository $commentaires,
        EntityManagerInterface $em
    ): RedirectResponse {
        $user = $this->requireForumRole();
        $comment = $commentaires->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        if ($comment->getAuteur()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Vous n\'êtes pas l\'auteur de ce commentaire.');
        }

        $postId = $comment->getPost()?->getId();

        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Votre commentaire a été supprimé.');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig');
    }
}
