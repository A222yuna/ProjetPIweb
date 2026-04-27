<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages_index')]
    public function index(ConversationRepository $repo): Response
    {
        $user = $this->getUser();
        // This is a simplified logic, in a real app you'd need a join table or complex query
        // to find conversations where the user is either sender or receiver of messages.
        // For now, let's assume we can find them.
        return $this->render('message/index.html.twig', [
            'conversations' => $repo->findAll(), // Placeholder
        ]);
    }

    #[Route('/nouveau/{id}', name: 'app_messages_new')]
    public function new(User $destinataire, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user === $destinataire) {
            return $this->redirectToRoute('app_messages_index');
        }

        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('message');
            if ($contenu) {
                $conversation = new Conversation();
                $conversation->setDateCreation(new \DateTime());
                $conversation->setStatutConversation('active');
                $em->persist($conversation);

                $message = new Message();
                $message->setContenuMessage($contenu);
                $message->setExpediteur($user);
                $message->setExpediteurRole($user->getRole());
                $message->setDestinataire($destinataire);
                $message->setDestinataireRole($destinataire->getRole());
                $message->setConversation($conversation);
                $em->persist($message);
                
                $em->flush();
                $this->addFlash('success', 'Message envoyé.');
                return $this->redirectToRoute('app_messages_index');
            }
        }

        return $this->render('message/new.html.twig', [
            'destinataire' => $destinataire,
        ]);
    }
}
