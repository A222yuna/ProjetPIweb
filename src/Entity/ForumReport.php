<?php

namespace App\Entity;

use App\Repository\ForumReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ForumReportRepository::class)]
#[ORM\Table(name: 'forum_report')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_forum_report_status_created')]
class ForumReport
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    public const ACTION_DISMISSED = 'dismissed';
    public const ACTION_HIDDEN = 'hidden';
    public const ACTION_UNHIDDEN = 'unhidden';
    public const ACTION_DELETED = 'deleted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'reporter_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $reporter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'post_id_post', referencedColumnName: 'id_post', nullable: true, onDelete: 'SET NULL')]
    private ?Post $targetPost = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'comment_id_comment', referencedColumnName: 'id_comment', nullable: true, onDelete: 'SET NULL')]
    private ?Commentaire $targetComment = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_OPEN])]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(name: 'resolution_action', length: 20, nullable: true)]
    private ?string $resolutionAction = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'resolved_by_id_user', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    private ?User $resolvedBy = null;

    #[ORM\Column(name: 'resolved_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[Assert\Callback]
    public function validateTarget(ExecutionContextInterface $context): void
    {
        $hasPost = $this->targetPost !== null;
        $hasComment = $this->targetComment !== null;

        if ($hasPost === $hasComment) {
            $context
                ->buildViolation('A report must target either a post or a comment.')
                ->atPath('targetPost')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;
        return $this;
    }

    public function getTargetPost(): ?Post
    {
        return $this->targetPost;
    }

    public function setTargetPost(?Post $targetPost): static
    {
        $this->targetPost = $targetPost;
        return $this;
    }

    public function getTargetComment(): ?Commentaire
    {
        return $this->targetComment;
    }

    public function setTargetComment(?Commentaire $targetComment): static
    {
        $this->targetComment = $targetComment;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getResolutionAction(): ?string
    {
        return $this->resolutionAction;
    }

    public function setResolutionAction(?string $resolutionAction): static
    {
        $this->resolutionAction = $resolutionAction;
        return $this;
    }

    public function getResolvedBy(): ?User
    {
        return $this->resolvedBy;
    }

    public function setResolvedBy(?User $resolvedBy): static
    {
        $this->resolvedBy = $resolvedBy;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

