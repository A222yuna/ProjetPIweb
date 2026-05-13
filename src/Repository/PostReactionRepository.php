<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\PostReaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostReaction>
 */
class PostReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostReaction::class);
    }

    public function findByPost(Post $post): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.post = :post')->setParameter('post', $post)
            ->getQuery()->getResult();
    }

    public function findByPostAndUser(Post $post, User $user): ?PostReaction
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.post = :post')->setParameter('post', $post)
            ->andWhere('r.user = :user')->setParameter('user', $user)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns ['❤️' => 3, '😂' => 1, ...] for a post
     * @return array<string,int>
     */
    public function countByEmoji(Post $post): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.emoji, COUNT(r.id) AS cnt')
            ->andWhere('r.post = :post')->setParameter('post', $post)
            ->groupBy('r.emoji')
            ->getQuery()->getArrayResult();

        $counts = [];
        foreach (PostReaction::EMOJIS as $e) {
            $counts[$e] = 0;
        }
        foreach ($rows as $row) {
            $counts[$row['emoji']] = (int) $row['cnt'];
        }
        return $counts;
    }
}
