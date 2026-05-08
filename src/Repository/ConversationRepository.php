<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Conversation> */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Custom function to find all active (non-archived) conversations for a user
     */
    public function findActiveConversationsByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.messages', 'm')
            ->where('c.archiverConversation = :archived')
            ->andWhere('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('archived', false)
            ->setParameter('user', $user)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
// src/Repository/ConversationRepository.php

public function findAllConversationsByUser(User $user): array
{
    return $this->createQueryBuilder('c')
        ->join('c.messages', 'm')
        ->where('m.expediteur = :user OR m.destinataire = :user')
        ->setParameter('user', $user)
        ->orderBy('c.dateCreation', 'DESC') // Most recent first
        ->getQuery()
        ->getResult();
}
    /**
     * CRUD: Save or Update a conversation
     */
    public function save(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * CRUD: Delete a conversation
     */
    public function remove(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}