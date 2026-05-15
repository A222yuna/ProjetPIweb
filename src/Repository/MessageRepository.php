<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Message> */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Custom function to get all messages in a conversation ordered by time
     */
    public function findMessagesByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conv')
            ->setParameter('conv', $conversation)
            ->orderBy('m.dateMessage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Custom function to count unread messages for a specific user
     */
    public function countUnreadMessages($user): int
    {
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.destinataire = :user')
            ->andWhere('m.estLu = :readStatus')
            ->setParameter('user', $user)
            ->setParameter('readStatus', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * CRUD: Save or Update a message
     */
    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * CRUD: Delete a message
     */
    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
