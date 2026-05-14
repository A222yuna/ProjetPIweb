<?php

namespace App\Entity;

use App\Repository\ForumNotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumNotificationRepository::class)]
#[ORM\Table(name: 'forum_notification')]
class ForumNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** The post author who receives the notification */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'recipient_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $recipient = null;

    /** The comment that triggered the notification */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'comment_id_comment', referencedColumnName: 'id_comment', nullable: true, onDelete: 'CASCADE')]
    private ?Commentaire $comment = null;

    /** The post being commented on */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'post_id_post', referencedColumnName: 'id_post', nullable: true, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\Column(name: 'is_read', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getRecipient(): ?User { return $this->recipient; }
    public function setRecipient(User $recipient): static { $this->recipient = $recipient; return $this; }

    public function getComment(): ?Commentaire { return $this->comment; }
    public function setComment(?Commentaire $comment): static { $this->comment = $comment; return $this; }

    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $post): static { $this->post = $post; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
