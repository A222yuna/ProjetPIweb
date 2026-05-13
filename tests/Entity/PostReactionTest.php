<?php

namespace App\Tests\Entity;

use App\Entity\PostReaction;
use App\Entity\Post;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PostReactionTest extends TestCase
{
    // ── Default values ────────────────────────────────────────────────────────

    public function testDefaultEmojiIsHeart(): void
    {
        $reaction = new PostReaction();
        $this->assertEquals('❤️', $reaction->getEmoji());
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before   = new \DateTime();
        $reaction = new PostReaction();
        $after    = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $reaction->getCreatedAt());
        $this->assertLessThanOrEqual($after,     $reaction->getCreatedAt());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $reaction = new PostReaction();
        $this->assertNull($reaction->getId());
    }

    // ── Valid emojis list ─────────────────────────────────────────────────────

    public function testEmojisConstantContainsFiveEmojis(): void
    {
        $this->assertCount(5, PostReaction::EMOJIS);
    }

    public function testEmojisConstantContainsExpectedValues(): void
    {
        $this->assertContains('❤️', PostReaction::EMOJIS);
        $this->assertContains('😂', PostReaction::EMOJIS);
        $this->assertContains('😮', PostReaction::EMOJIS);
        $this->assertContains('😢', PostReaction::EMOJIS);
        $this->assertContains('👏', PostReaction::EMOJIS);
    }

    // ── Setters ───────────────────────────────────────────────────────────────

    public function testSetEmoji(): void
    {
        $reaction = new PostReaction();
        $reaction->setEmoji('😂');
        $this->assertEquals('😂', $reaction->getEmoji());
    }

    public function testSetPost(): void
    {
        $post     = $this->createMock(Post::class);
        $reaction = new PostReaction();
        $reaction->setPost($post);

        $this->assertSame($post, $reaction->getPost());
    }

    public function testSetUser(): void
    {
        $user     = $this->createMock(User::class);
        $reaction = new PostReaction();
        $reaction->setUser($user);

        $this->assertSame($user, $reaction->getUser());
    }

    // ── Full reaction build ───────────────────────────────────────────────────

    public function testFullReactionBuild(): void
    {
        $post = $this->createMock(Post::class);
        $user = $this->createMock(User::class);

        $reaction = (new PostReaction())
            ->setPost($post)
            ->setUser($user)
            ->setEmoji('👏');

        $this->assertSame($post, $reaction->getPost());
        $this->assertSame($user, $reaction->getUser());
        $this->assertEquals('👏', $reaction->getEmoji());
    }
}
