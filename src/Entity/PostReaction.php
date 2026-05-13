<?php

namespace App\Entity;

use App\Repository\PostReactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostReactionRepository::class)]
#[ORM\Table(name: 'post_reaction')]
#[ORM\UniqueConstraint(name: 'uq_post_user_reaction', columns: ['post_id', 'user_id'])]
class PostReaction
{
    public const EMOJIS = ['❤️', '😂', '😮', '😢', '👏'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id_post', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 10)]
    private string $emoji = '❤️';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getPost(): ?Post { return $this->post; }
    public function setPost(Post $post): static { $this->post = $post; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getEmoji(): string { return $this->emoji; }
    public function setEmoji(string $emoji): static { $this->emoji = $emoji; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
