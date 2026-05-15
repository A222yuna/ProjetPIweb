<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ChatController extends AbstractController
{
    private $httpClient;
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/', name: 'app_chat_inbox')]
    public function index(ConversationRepository $convRepo, UserRepository $userRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $conversations = $convRepo->findAllConversationsByUser($user);

        return $this->render('chat/index.html.twig', [
            'conversations' => $conversations,
            'currentUser' => $user,
        ]);
    }

    #[Route('/message/suggest/{id}', name: 'app_message_ai_suggest')]
    public function suggestImprovement(
        Message $message,
        HttpClientInterface $httpClient
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $apiKey = "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";

        try {
            $response = $httpClient->request('POST', 'https://integrate.api.nvidia.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'google/gemma-2-2b-it',
                    'messages' => [
                        [
                            'role' => 'user', 
                            'content' => "Improve this message to be more professional, polite, and clear. Return only the improved text: " . $message->getContenuMessage()
                        ]
                    ],
                    'temperature' => 0.2,
                    'top_p' => 0.7,
                    'max_tokens' => 1024,
                ],
            ]);

            $data = $response->toArray();
            
            $suggestion = $data['choices'][0]['message']['content'] ?? 'No suggestion available.';
            $this->addFlash('ai_suggestion_' . $message->getId(), trim($suggestion));

        } catch (\Exception $e) {
            $this->addFlash('error', 'AI Service currently unavailable.');
        }

        return $this->redirectToRoute('app_chat_view', [
            'id' => $message->getConversation()->getId()
        ]);
    }

    #[Route('/export/{id}', name: 'app_chat_export_pdf')]
    public function exportPdf(
        Conversation $conversation, 
        MessageRepository $msgRepo,
        HttpClientInterface $httpClient
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $messages = $msgRepo->findBy(['conversation' => $conversation], ['dateMessage' => 'ASC']);
        
        $payload = [
            "conv_number" => (int) $conversation->getId(),
            "message_list" => []
        ];

        foreach ($messages as $msg) {
            $payload["message_list"][] = [
                "sender"  => $msg->getExpediteur()->getPrenom(),
                "content" => $msg->getContenuMessage(),
                "date"    => $msg->getDateMessage()->format('d/m/Y H:i:s')
            ];
        }

        try {
            $response = $httpClient->request('POST', 'https://api.pdfmonkey.io/api/v1/documents', [
                'headers' => [
                    'Authorization' => 'Bearer g83DV6z_PZh34sL-N2v7',
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'document' => [
                        'document_template_id' => '890E5262-90A8-4647-8F7A-8D8BE97413C4',
                        'status'               => 'pending',
                        'payload'              => $payload,
                    ],
                ],
            ]);

            $data = $response->toArray();
            $documentId = $data['document']['id'];

            $attempts = 0;
            while ($attempts < 5) {
                $statusCheck = $httpClient->request('GET', "https://api.pdfmonkey.io/api/v1/documents/$documentId", [
                    'headers' => ['Authorization' => 'Bearer g83DV6z_PZh34sL-N2v7']
                ]);
                
                $docData = $statusCheck->toArray();
                $downloadUrl = $docData['document']['download_url'] ?? null;

                if ($downloadUrl) {
                    return $this->redirect($downloadUrl);
                }

                sleep(1);
                $attempts++;
            }

            $this->addFlash('error', 'PDF is taking too long to generate. Please check your dashboard.');
      
        } catch (\Exception $e) {
            $this->addFlash('error', 'PDF Generation failed: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_chat_view', ['id' => $conversation->getId()]);
    }

    #[Route('/message/translate/{id}', name: 'app_message_translate')]
    public function translateMessage(
        Message $message,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $targetLang = $request->query->get('lang', 'fr');
        $sourceText = $message->getContenuMessage();

        $url = sprintf(
            'https://api.mymemory.translated.net/get?q=%s&langpair=%s|%s',
            urlencode($sourceText),
            'autodetect',
            $targetLang
        );

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        $translatedText = $data['responseData']['translatedText'] ?? 'Translation failed.';
        $this->addFlash('translation_' . $message->getId(), $translatedText);

        return $this->redirectToRoute('app_chat_view', [
            'id' => $message->getConversation()->getId()
        ]);
    }

    #[Route('/view/{id}', name: 'app_chat_view')]
    public function view(
        Conversation $conversation,
        MessageRepository $msgRepo,
        UserRepository $userRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $messages = $msgRepo->findMessagesByConversation($conversation);

        if ($request->isMethod('POST')) {
            if ($conversation->getStatutConversation() === 'not active') {
                $this->addFlash('error', 'You cannot send messages to an inactive conversation.');
                return $this->redirectToRoute('app_chat_view', [
                    'id' => $conversation->getId()
                ]);
            }
            $content = $request->request->get('message');
            if ($content) {
                $message = new Message();
                $message->setContenuMessage($content);
                $message->setConversation($conversation);
                $message->setExpediteur($user);
                $message->setExpediteurRole($user->getRole());

                $recipientId = $request->request->get('recipientId');
                $recipient = $userRepo->find($recipientId);
                
                if (!$recipient) {
                    foreach ($messages as $msg) {
                        if ($msg->getExpediteur() !== $user) {
                            $recipient = $msg->getExpediteur();
                            break;
                        }
                        if ($msg->getDestinataire() !== $user) {
                            $recipient = $msg->getDestinataire();
                            break;
                        }
                    }
                }
                
                $message->setDestinataire($recipient);
                $message->setDestinataireRole($recipient ? $recipient->getRole() : 'User');
                $message->setDateMessage(new \DateTime());

                $em->persist($message);
                $em->flush();

                return $this->redirectToRoute('app_chat_view', [
                    'id' => $conversation->getId()
                ]);
            }
        }

        return $this->render('chat/view.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
            'currentUser' => $user
        ]);
    }

    #[Route('/new', name: 'app_chat_new_search')]
    public function searchUser(UserRepository $userRepo): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $users = $userRepo->createQueryBuilder('u')
            ->where('u.id != :id')
            ->setParameter('id', $currentUser->getId())
            ->getQuery()
            ->getResult();

        return $this->render('chat/new_search.html.twig', [
            'users' => $users,
            'currentUser' => $currentUser
        ]);
    }

    #[Route('/message/delete/{id}', name: 'app_message_delete')]
    public function deleteMessage(
        Message $message,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversationId = $message->getConversation()->getId();
        $em->remove($message);
        $em->flush();

        return $this->redirectToRoute('app_chat_view', [
            'id' => $conversationId
        ]);
    }

    #[Route('/ai/improve-preview', name: 'app_chat_ai_improve_preview', methods: ['POST'])]
    public function improvePreview(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $content = $request->request->get('text');
        $apiKey = "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";

        if (!$content) {
            return new JsonResponse(['error' => 'No text provided'], 400);
        }

        try {
            $response = $httpClient->request('POST', 'https://integrate.api.nvidia.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'google/gemma-2-2b-it',
                    'messages' => [
                        ['role' => 'user', 'content' => "Rewrite this chat message to be more professional and clear. Return ONLY the rewritten text: " . $content]
                    ],
                    'temperature' => 0.2,
                ],
            ]);

            $data = $response->toArray();
            $suggestion = $data['choices'][0]['message']['content'] ?? $content;

            return new JsonResponse(['suggestion' => trim($suggestion)]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'AI Service Error'], 500);
        }
    }

    #[Route('/message/edit/{id}', name: 'app_message_edit')]
    public function editMessage(
        Message $message, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $suggestedContent = $request->query->get('suggestion');

        if ($request->isMethod('POST')) {
            $newContent = $request->request->get('message');
            
            if ($newContent !== null && trim($newContent) !== '') {
                $message->setContenuMessage($newContent);
                $em->flush();
            }

            return $this->redirectToRoute('app_chat_view', [
                'id' => $message->getConversation()->getId()
            ]);
        }

        return $this->render('chat/edit_message.html.twig', [
            'message' => $message,
            'initialContent' => $suggestedContent ?? $message->getContenuMessage()
        ]);
    }

    #[Route('/create/{recipientId}', name: 'app_chat_create')]
    public function createConversation(
        int $recipientId,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $recipient = $userRepo->find($recipientId);

        $conversation = new Conversation();
        $conversation->setDateCreation(new \DateTime());
        $conversation->setStatutConversation('active');
        $conversation->setArchiverConversation(false);

        $em->persist($conversation);

        $message = new Message();
        $message->setContenuMessage("Conversation started.");
        $message->setConversation($conversation);
        $message->setExpediteur($currentUser);
        $message->setExpediteurRole($currentUser->getRole());
        $message->setDestinataire($recipient);
        $message->setDestinataireRole($recipient->getRole());
        $message->setDateMessage(new \DateTime());

        $em->persist($message);
        $em->flush();

        return $this->redirectToRoute('app_chat_view', [
            'id' => $conversation->getId()
        ]);
    }

    #[Route('/toggle-status/{id}', name: 'app_chat_toggle_status')]
    public function toggleStatus(
        Conversation $conversation,
        EntityManagerInterface $em
    ): Response {
        if ($conversation->getStatutConversation() === 'not active') {
            $conversation->setStatutConversation('active');
            $conversation->setArchiverConversation(false);
        } else {
            $conversation->setStatutConversation('not active');
            $conversation->setArchiverConversation(true);
        }

        $em->flush();
        return $this->redirectToRoute('app_chat_inbox');
    }

    #[Route('/delete/{id}', name: 'app_chat_delete')]
    public function delete(
        Conversation $conversation,
        EntityManagerInterface $em
    ): Response {
        $em->remove($conversation);
        $em->flush();
        return $this->redirectToRoute('app_chat_inbox');
    }
}
