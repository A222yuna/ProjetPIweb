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

    #[Route('/consultation', name: 'app_consultation', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PostRepository $posts,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->requireForumRole();
        $q = $request->query->getString('q');
        $filterCategorie = $request->query->getString('categorie');
        $page = max(1, $request->query->getInt('page', 1));

        $categorySuggestions = $posts->findDistinctCategories();

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
            $this->addFlash('success', 'Votre publication a été enregistrée.');

            $redirectParams = [];
            if ($q !== '') {
                $redirectParams['q'] = $q;
            }
            if ($filterCategorie !== '') {
                $redirectParams['categorie'] = $filterCategorie;
            }

            return $this->redirectToRoute('app_consultation', $redirectParams);
        }

        $result = $posts->searchConsultationsPaginated(
            $q !== '' ? $q : null,
            $filterCategorie !== '' ? $filterCategorie : null,
            $page,
            6
        );
        $totalPages = max(1, (int) ceil($result['total'] / 6));

        return $this->render('consultation/index.html.twig', [
            'posts' => $result['items'],
            'form' => $form,
            'q' => $q,
            'filter_categorie' => $filterCategorie,
            'category_suggestions' => $categorySuggestions,
            'show_post_modal' => $form->isSubmitted() && !$form->isValid(),
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

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig');
    }
}
