<?php

namespace App\Tests\Entity;

use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    // ── Views counter ─────────────────────────────────────────────────────────

    public function testNbViewsStartsAtZero(): void
    {
        $post = new Post();
        $this->assertEquals(0, $post->getNbViews());
    }

    public function testIncrementViewsAddsOne(): void
    {
        $post = new Post();
        $post->incrementViews();
        $this->assertEquals(1, $post->getNbViews());
    }

    public function testIncrementViewsMultipleTimes(): void
    {
        $post = new Post();
        $post->incrementViews();
        $post->incrementViews();
        $post->incrementViews();
        $this->assertEquals(3, $post->getNbViews());
    }

    // ── Anonymous posting ─────────────────────────────────────────────────────

    public function testIsAnonymousFalseByDefault(): void
    {
        $post = new Post();
        $this->assertFalse($post->isAnonymous());
    }

    public function testSetIsAnonymousTrue(): void
    {
        $post = new Post();
        $post->setIsAnonymous(true);
        $this->assertTrue($post->isAnonymous());
    }

    public function testSetIsAnonymousFalse(): void
    {
        $post = new Post();
        $post->setIsAnonymous(true);
        $post->setIsAnonymous(false);
        $this->assertFalse($post->isAnonymous());
    }

    // ── Likes ─────────────────────────────────────────────────────────────────

    public function testNbLikesStartsAtZero(): void
    {
        $post = new Post();
        $this->assertEquals(0, $post->getNbLikes());
    }

    public function testSetNbLikes(): void
    {
        $post = new Post();
        $post->setNbLikes(5);
        $this->assertEquals(5, $post->getNbLikes());
    }

    // ── Hidden ────────────────────────────────────────────────────────────────

    public function testIsHiddenFalseByDefault(): void
    {
        $post = new Post();
        $this->assertFalse($post->isHidden());
    }

    public function testSetIsHidden(): void
    {
        $post = new Post();
        $post->setIsHidden(true);
        $this->assertTrue($post->isHidden());
    }

    // ── Basic fields ──────────────────────────────────────────────────────────

    public function testSetAndGetTitre(): void
    {
        $post = new Post();
        $post->setTitre('Mon premier post');
        $this->assertEquals('Mon premier post', $post->getTitre());
    }

    public function testSetAndGetContenu(): void
    {
        $post = new Post();
        $post->setContenu('Contenu du post de test.');
        $this->assertEquals('Contenu du post de test.', $post->getContenu());
    }

    public function testCommentairesCollectionIsEmptyByDefault(): void
    {
        $post = new Post();
        $this->assertCount(0, $post->getCommentaires());
    }
}
