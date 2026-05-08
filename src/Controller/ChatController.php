<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/chat')]
class ChatController extends AbstractController
{
    private $httpClient;
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/{userId}', name: 'app_chat_inbox')]
    public function index(int $userId, ConversationRepository $convRepo, UserRepository $userRepo): Response
    {
        $user = $userRepo->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Change findActiveConversationsByUser to findAllConversationsByUser
        $conversations = $convRepo->findAllConversationsByUser($user);

        return $this->render('chat/index.html.twig', [
            'conversations' => $conversations,
            'currentUser' => $user,
        ]);
    }


    // src/Controller/ChatController.php

// src/Controller/ChatController.php
#[Route('/{userId}/message/suggest/{id}', name: 'app_message_ai_suggest')]
public function suggestImprovement(
    int $userId,
    Message $message,
    HttpClientInterface $httpClient
): Response {
    // Hardcoded key as requested
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
        
        // Extracting the content from the NVIDIA/OpenAI response format
        $suggestion = $data['choices'][0]['message']['content'] ?? 'No suggestion available.';

        // Storing in a unique flash message for this specific message ID
        $this->addFlash('ai_suggestion_' . $message->getId(), trim($suggestion));

    } catch (\Exception $e) {
        $this->addFlash('error', 'AI Service currently unavailable.');
    }

    return $this->redirectToRoute('app_chat_view', [
        'userId' => $userId,
        'id' => $message->getConversation()->getId()
    ]);
}
#[Route('/{userId}/export/{id}', name: 'app_chat_export_pdf')]
public function exportPdf(
    int $userId, 
    Conversation $conversation, 
    MessageRepository $msgRepo,
    HttpClientInterface $httpClient
): Response {
    // 1. Fetch messages for this specific conversation
    $messages = $msgRepo->findBy(['conversation' => $conversation], ['dateMessage' => 'ASC']);
    
    // 2. Build the numerical array for 'message_list'
    $messageList = [];
    foreach ($messages as $msg) {
        $messageList[] = [
            'sender'  => $msg->getExpediteur()->getPrenom(),
            'content' => $msg->getContenuMessage(),
            'date'    => $msg->getDateMessage()->format('d/m/Y H:i:s')
        ];
    }

    // 3. Assemble the full payload exactly as PDFMonkey expects
$payload = [
    "conv_number" => (int) $conversation->getId(), // Ensures it's an integer
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

    // --- DIRECT DOWNLOAD LOGIC ---
    // We loop briefly to wait for the generation to finish (max 5 seconds)
    $attempts = 0;
    while ($attempts < 5) {
        $statusCheck = $httpClient->request('GET', "https://api.pdfmonkey.io/api/v1/documents/$documentId", [
            'headers' => ['Authorization' => 'Bearer g83DV6z_PZh34sL-N2v7']
        ]);
        
        $docData = $statusCheck->toArray();
        $downloadUrl = $docData['document']['download_url'] ?? null;

        if ($downloadUrl) {
            // This is the magic part: redirecting to the direct Amazon S3/PDF link
            return $this->redirect($downloadUrl);
        }

        sleep(1); // Wait 1 second before checking again
        $attempts++;
    }

    $this->addFlash('error', 'PDF is taking too long to generate. Please check your dashboard.');
      

    } catch (\Exception $e) {
        $this->addFlash('error', 'PDF Generation failed: ' . $e->getMessage());
    }

    return $this->redirectToRoute('app_chat_view', ['userId' => $userId, 'id' => $conversation->getId()]);
}

    #[Route('/{userId}/message/translate/{id}', name: 'app_message_translate')]
    public function translateMessage(
        int $userId,
        Message $message,
        Request $request
    ): Response {
        $targetLang = $request->query->get('lang', 'fr'); // Default to French
        $sourceText = $message->getContenuMessage();

        // MyMemory API call
        $url = sprintf(
            'https://api.mymemory.translated.net/get?q=%s&langpair=%s|%s',
            urlencode($sourceText),
            'autodetect', // Source language
            $targetLang   // Target language
        );

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        $translatedText = $data['responseData']['translatedText'] ?? 'Translation failed.';

        // We pass the translated text back to the view using a flash or a query param
        $this->addFlash('translation_' . $message->getId(), $translatedText);

        return $this->redirectToRoute('app_chat_view', [
            'userId' => $userId,
            'id' => $message->getConversation()->getId()
        ]);
    }

    // The path now includes {userId} and {id} (conversation id)
    #[Route('/{userId}/view/{id}', name: 'app_chat_view')]
    public function view(
        int $userId,
        Conversation $conversation,
        MessageRepository $msgRepo,
        UserRepository $userRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepo->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Fetch messages for this specific conversation
        $messages = $msgRepo->findMessagesByConversation($conversation);

        if ($request->isMethod('POST')) {
            if ($conversation->getStatutConversation() === 'not active') {
                $this->addFlash('error', 'You cannot send messages to an inactive conversation.');
                return $this->redirectToRoute('app_chat_view', [
                    'userId' => $userId,
                    'id' => $conversation->getId()
                ]);
            }
            $content = $request->request->get('message');
            if ($content) {
                $message = new Message();
                $message->setContenuMessage($content);
                $message->setConversation($conversation);
                $message->setExpediteur($user);
                // Set role from User entity
                $message->setExpediteurRole($user->getRole());

                // Logic to find the other user in the conversation
                // For now, we'll set a default or logic to find the recipient
                $recipientId = $request->request->get('recipientId');
           
                $recipient = $userRepo->find($recipientId);
                $message->setDestinataire($recipient);
                $message->setDestinataireRole($recipient ? $recipient->getRole() : 'User');

                $em->persist($message);
                $em->flush();

                return $this->redirectToRoute('app_chat_view', [
                    'userId' => $user->getId(),
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

    #[Route('/{userId}/new', name: 'app_chat_new_search')]
    public function searchUser(int $userId, UserRepository $userRepo): Response
    {
        $currentUser = $userRepo->find($userId);
        // Fetch all users except the current one to start a chat with
        $users = $userRepo->createQueryBuilder('u')
            ->where('u.id != :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();

        return $this->render('chat/new_search.html.twig', [
            'users' => $users,
            'currentUser' => $currentUser
        ]);
    }
    #[Route('/{userId}/message/delete/{id}', name: 'app_message_delete')]
    public function deleteMessage(
        int $userId,
        Message $message,
        EntityManagerInterface $em
    ): Response {
        $conversationId = $message->getConversation()->getId();

        // Standard removal from the database
        $em->remove($message);
        $em->flush();

        return $this->redirectToRoute('app_chat_view', [
            'userId' => $userId,
            'id' => $conversationId
        ]);
    }

 // src/Controller/ChatController.php
#[Route('/chat/ai/improve-preview', name: 'app_chat_ai_improve_preview', methods: ['POST'])]
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
#[Route('/{userId}/message/edit/{id}', name: 'app_message_edit')]
public function editMessage(
    int $userId, 
    Message $message, 
    Request $request, 
    EntityManagerInterface $em
): Response {
    // Check if an AI suggestion was passed in the URL
    $suggestedContent = $request->query->get('suggestion');

    if ($request->isMethod('POST')) {
        $newContent = $request->request->get('message');
        
        if ($newContent !== null && trim($newContent) !== '') {
            $message->setContenuMessage($newContent);
            $em->flush();
        }

        return $this->redirectToRoute('app_chat_view', [
            'userId' => $userId, 
            'id' => $message->getConversation()->getId()
        ]);
    }

    return $this->render('chat/edit_message.html.twig', [
        'message' => $message,
        'userId' => $userId,
        // If we have a suggestion, use it; otherwise, use the original database content
        'initialContent' => $suggestedContent ?? $message->getContenuMessage()
    ]);
}
    #[Route('/{userId}/create/{recipientId}', name: 'app_chat_create')]
    public function createConversation(
        int $userId,
        int $recipientId,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $userRepo->find($userId);
        $recipient = $userRepo->find($recipientId);

        // 1. Create the new conversation
        $conversation = new Conversation();
        $conversation->setDateCreation(new \DateTime());
        $conversation->setStatutConversation('active');
        $conversation->setArchiverConversation(false);

        $em->persist($conversation);

        // 2. Create an initial "system" message or empty message to link the users
        // This is necessary because your findActiveConversationsByUser logic 
        // joins through the message table
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
            'userId' => $userId,
            'id' => $conversation->getId()
        ]);
    }

    #[Route('/{userId}/toggle-status/{id}', name: 'app_chat_toggle_status')]
    public function toggleStatus(
        int $userId,
        Conversation $conversation,
        EntityManagerInterface $em
    ): Response {
        // Logic: Switch between 'active' and 'not active'
        if ($conversation->getStatutConversation() === 'not active') {
            $conversation->setStatutConversation('active');
            $conversation->setArchiverConversation(false);
        } else {
            $conversation->setStatutConversation('not active');
            $conversation->setArchiverConversation(true);
        }

        $em->flush();

        return $this->redirectToRoute('app_chat_inbox', ['userId' => $userId]);
    }

    #[Route('/{userId}/delete/{id}', name: 'app_chat_delete')]
    public function delete(
        int $userId,
        Conversation $conversation,
        EntityManagerInterface $em
    ): Response {
        // The 'onDelete: CASCADE' in your Message entity will automatically 
        // delete all messages belonging to this conversation
        $em->remove($conversation);
        $em->flush();

        return $this->redirectToRoute('app_chat_inbox', ['userId' => $userId]);
    }
}
