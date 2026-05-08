<?php
// tests/MessageAndChatTest.php

namespace App\Tests;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Basic tests for Message entity and Message repository only.
 */
class MessageAndChatTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private ?MessageRepository $messageRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->messageRepository = $this->entityManager->getRepository(Message::class);

        // Clean tables before each test
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Message')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Conversation')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    // Helper: create a complete User with all required fields
    private function createMinimalUser(string $email, string $role, string $nom = 'Test', string $prenom = 'User'): User
    {
        $user = new User();
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setMotDePasse('password123'); // required
        $user->setRole($role);
        $user->setDateInscription(new \DateTime()); // optional but good
        $user->setStatutValidation('approuve'); // default value but explicit
        $this->entityManager->persist($user);
        return $user;
    }

    private function createMinimalConversation(): Conversation
    {
        $conv = new Conversation();
        $conv->setDateCreation(new \DateTime());
        $conv->setStatutConversation('active');
        $conv->setArchiverConversation(false);
        $this->entityManager->persist($conv);
        return $conv;
    }

    private function createMinimalMessage(Conversation $conv, User $from, User $to, string $content, bool $isRead = false): Message
    {
        $msg = new Message();
        $msg->setContenuMessage($content);
        $msg->setConversation($conv);
        $msg->setExpediteur($from);
        $msg->setExpediteurRole($from->getRole());
        $msg->setDestinataire($to);
        $msg->setDestinataireRole($to->getRole());
        $msg->setEstLu($isRead);
        $msg->setDateMessage(new \DateTime());
        $this->entityManager->persist($msg);
        return $msg;
    }

    // -------------------- Message Entity Tests --------------------
    public function testProfanityFilter(): void
    {
        $message = new Message();
        $message->setContenuMessage("This is shit and fuck you haha asshole");
        $message->handleProfanityFilter();

        $filtered = $message->getContenuMessage();
        $this->assertStringNotContainsString('shit', $filtered);
        $this->assertStringNotContainsString('fuck', $filtered);
        $this->assertStringNotContainsString('asshole', $filtered);
        $this->assertStringContainsString('****', $filtered);
    }

    public function testConstructorSetsDate(): void
    {
        $message = new Message();
        $this->assertInstanceOf(\DateTimeInterface::class, $message->getDateMessage());
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $message->getDateMessage()->getTimestamp();
        $this->assertLessThan(2, $diff);
    }

    public function testMessageSettersAndGetters(): void
    {
        $message = new Message();
        $date = new \DateTime('2025-01-01 12:00:00');

        $message->setContenuMessage('Hello');
        $message->setDateMessage($date);
        $message->setEstLu(true);
        $message->setExpediteurRole('ROLE_USER');
        $message->setDestinataireRole('ROLE_ADMIN');

        $this->assertEquals('Hello', $message->getContenuMessage());
        $this->assertSame($date, $message->getDateMessage());
        $this->assertTrue($message->isEstLu());
        $this->assertEquals('ROLE_USER', $message->getExpediteurRole());
        $this->assertEquals('ROLE_ADMIN', $message->getDestinataireRole());
    }

    // -------------------- Message Repository Tests --------------------
    public function testFindMessagesByConversationOrder(): void
    {
        $user1 = $this->createMinimalUser('alice@test.com', 'Patient');
        $user2 = $this->createMinimalUser('bob@test.com', 'Psychologue');
        $conv = $this->createMinimalConversation();
        $this->entityManager->flush();

        $this->createMinimalMessage($conv, $user1, $user2, 'First message');
        $this->createMinimalMessage($conv, $user2, $user1, 'Second message');
        $this->entityManager->flush();

        $messages = $this->messageRepository->findMessagesByConversation($conv);
        $this->assertCount(2, $messages);
        $this->assertEquals('First message', $messages[0]->getContenuMessage());
        $this->assertEquals('Second message', $messages[1]->getContenuMessage());
    }

    public function testCountUnreadMessages(): void
    {
        $user1 = $this->createMinimalUser('alice@test.com', 'Patient');
        $user2 = $this->createMinimalUser('bob@test.com', 'Psychologue');
        $conv = $this->createMinimalConversation();
        $this->entityManager->flush();

        $this->createMinimalMessage($conv, $user1, $user2, 'Unread 1', false);
        $this->createMinimalMessage($conv, $user1, $user2, 'Unread 2', false);
        $this->createMinimalMessage($conv, $user2, $user1, 'Read', true);
        $this->entityManager->flush();

        $unread = $this->messageRepository->countUnreadMessages($user2);
        $this->assertEquals(2, $unread);
    }

    public function testSaveAndRemoveMessage(): void
    {
        $user = $this->createMinimalUser('test@test.com', 'Patient');
        $conv = $this->createMinimalConversation();
        $this->entityManager->flush();

        $msg = new Message();
        $msg->setContenuMessage('Temporary');
        $msg->setConversation($conv);
        $msg->setExpediteur($user);
        $msg->setExpediteurRole($user->getRole());
        $msg->setDestinataire($user);
        $msg->setDestinataireRole($user->getRole());

        $this->messageRepository->save($msg, true);
        $id = $msg->getId();
        $this->assertNotNull($id);

        $this->messageRepository->remove($msg, true);
        $found = $this->messageRepository->find($id);
        $this->assertNull($found);
    }
}