<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\ForumReport;
use App\Entity\Post;
use App\Form\ForumReportType;
use App\Repository\CommentaireRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ForumReportController extends AbstractController
{
    private function requireForumRole(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new AccessDeniedException('Vous devez être connecté.');
        }
        if (!\in_array($user->getRole(), ['Patient', 'Psychologue'], true)) {
            throw new AccessDeniedException('Les administrateurs ne peuvent pas soumettre de signalement.');
        }

        return $user;
    }

    #[Route('/consultation/{id}/report', name: 'app_post_report', methods: ['GET', 'POST'])]
    public function reportPost(int $id, Request $request, PostRepository $posts, EntityManagerInterface $em): Response
    {
        $user = $this->requireForumRole();
        $post = $posts->find($id);
        if (!$post instanceof Post) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $form = $this->createForm(ForumReportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{reason:string, details?:string|null} $data */
            $data = $form->getData();

            $report = (new ForumReport())
                ->setReporter($user)
                ->setTargetPost($post)
                ->setReason((string) $data['reason'])
                ->setDetails(($data['details'] ?? null) !== '' ? ($data['details'] ?? null) : null);

            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Merci. Votre signalement a été envoyé à l’administrateur.');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('report/new.html.twig', [
            'title' => 'Signaler une publication',
            'back_url' => $this->generateUrl('app_post_show', ['id' => $post->getId()]),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/consultation/commentaire/{id}/report', name: 'app_comment_report', methods: ['GET', 'POST'])]
    public function reportComment(int $id, Request $request, CommentaireRepository $comments, EntityManagerInterface $em): Response
    {
        $user = $this->requireForumRole();
        $comment = $comments->find($id);
        if (!$comment instanceof Commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        $postId = $comment->getPost()?->getId();
        if (!$postId) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $form = $this->createForm(ForumReportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{reason:string, details?:string|null} $data */
            $data = $form->getData();

            $report = (new ForumReport())
                ->setReporter($user)
                ->setTargetComment($comment)
                ->setReason((string) $data['reason'])
                ->setDetails(($data['details'] ?? null) !== '' ? ($data['details'] ?? null) : null);

            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Merci. Votre signalement a été envoyé à l’administrateur.');
            return $this->redirectToRoute('app_post_show', ['id' => $postId]);
        }

        return $this->render('report/new.html.twig', [
            'title' => 'Signaler un commentaire',
            'back_url' => $this->generateUrl('app_post_show', ['id' => $postId]),
            'form' => $form->createView(),
        ]);
    }
}

