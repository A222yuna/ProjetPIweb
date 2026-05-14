<?php

namespace App\Tests\Entity;

use App\Entity\ForumNotification;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Commentaire;
use PHPUnit\Framework\TestCase;

class ForumNotificationTest extends TestCase
{
    // ── Default values ────────────────────────────────────────────────────────

    public function testIsReadFalseByDefault(): void
    {
        $notif = new ForumNotification();
        $this->assertFalse($notif->isRead());
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $notif  = new ForumNotification();
        $after  = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $notif->getCreatedAt());
        $this->assertLessThanOrEqual($after,    $notif->getCreatedAt());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $notif = new ForumNotification();
        $this->assertNull($notif->getId());
    }

    // ── Setters ───────────────────────────────────────────────────────────────

    public function testSetRecipient(): void
    {
        $user  = $this->createMock(User::class);
        $notif = new ForumNotification();
        $notif->setRecipient($user);

        $this->assertSame($user, $notif->getRecipient());
    }

    public function testSetPost(): void
    {
        $post  = $this->createMock(Post::class);
        $notif = new ForumNotification();
        $notif->setPost($post);

        $this->assertSame($post, $notif->getPost());
    }

    public function testSetComment(): void
    {
        $comment = $this->createMock(Commentaire::class);
        $notif   = new ForumNotification();
        $notif->setComment($comment);

        $this->assertSame($comment, $notif->getComment());
    }

    public function testMarkAsRead(): void
    {
        $notif = new ForumNotification();
        $this->assertFalse($notif->isRead());

        $notif->setIsRead(true);
        $this->assertTrue($notif->isRead());
    }

    // ── Full notification build ───────────────────────────────────────────────

    public function testFullNotificationBuild(): void
    {
        $user    = $this->createMock(User::class);
        $post    = $this->createMock(Post::class);
        $comment = $this->createMock(Commentaire::class);

        $notif = (new ForumNotification())
            ->setRecipient($user)
            ->setPost($post)
            ->setComment($comment);

        $this->assertSame($user,    $notif->getRecipient());
        $this->assertSame($post,    $notif->getPost());
        $this->assertSame($comment, $notif->getComment());
        $this->assertFalse($notif->isRead());
    }
}
