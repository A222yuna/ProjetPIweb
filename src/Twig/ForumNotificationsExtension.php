<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\CommentaireRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ForumNotificationsExtension extends AbstractExtension
{
    public function __construct(private readonly CommentaireRepository $comments)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('forum_notifications', [$this, 'getForumNotifications']),
        ];
    }

    /**
     * @return array{items: array<\App\Entity\Commentaire>, total: int}
     */
    public function getForumNotifications(?User $user, int $limit = 6): array
    {
        if (!$user instanceof User) {
            return ['items' => [], 'total' => 0];
        }

        $items = $this->comments->findLatestCommentsOnUserPosts($user, $limit);

        return [
            'items' => $items,
            'total' => \count($items),
        ];
    }
}

