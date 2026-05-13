<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Repository\ForumNotificationRepository;
use App\Twig\ForumNotificationsExtension;
use PHPUnit\Framework\TestCase;

class ForumNotificationsExtensionTest extends TestCase
{
    // ── Null user ─────────────────────────────────────────────────────────────

    public function testReturnsZeroForNullUser(): void
    {
        $repo = $this->createMock(ForumNotificationRepository::class);
        $repo->expects($this->never())->method('countUnread');

        $ext = new ForumNotificationsExtension($repo);
        $this->assertEquals(0, $ext->getUnreadCount(null));
    }

    // ── Real user with unread notifications ───────────────────────────────────

    public function testReturnsCountForAuthenticatedUser(): void
    {
        $user = $this->createMock(User::class);

        $repo = $this->createMock(ForumNotificationRepository::class);
        $repo->expects($this->once())
             ->method('countUnread')
             ->with($user)
             ->willReturn(3);

        $ext = new ForumNotificationsExtension($repo);
        $this->assertEquals(3, $ext->getUnreadCount($user));
    }

    // ── User with zero unread ─────────────────────────────────────────────────

    public function testReturnsZeroWhenNoUnread(): void
    {
        $user = $this->createMock(User::class);

        $repo = $this->createMock(ForumNotificationRepository::class);
        $repo->method('countUnread')->willReturn(0);

        $ext = new ForumNotificationsExtension($repo);
        $this->assertEquals(0, $ext->getUnreadCount($user));
    }

    // ── Twig function registration ────────────────────────────────────────────

    public function testRegistersTwigFunction(): void
    {
        $repo = $this->createMock(ForumNotificationRepository::class);
        $ext = new ForumNotificationsExtension($repo);

        $functionNames = array_map(
            fn($f) => $f->getName(),
            $ext->getFunctions()
        );

        $this->assertContains('forum_unread_count', $functionNames);
    }
}
