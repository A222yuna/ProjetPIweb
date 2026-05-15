<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\ForumNotificationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ForumNotificationsExtension extends AbstractExtension
{
    public function __construct(private readonly ForumNotificationRepository $notifRepo)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('forum_unread_count', [$this, 'getUnreadCount']),
        ];
    }

    public function getUnreadCount(?User $user): int
    {
        if (!$user instanceof User) {
            return 0;
        }

        return $this->notifRepo->countUnread($user);
    }
}
