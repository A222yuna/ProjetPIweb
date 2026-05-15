<?php

namespace App\Entity;

use App\Repository\SlotHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Audit log for all slot-related actions.
 */
#[ORM\Entity(repositoryClass: SlotHistoryRepository::class)]
#[ORM\Table(name: 'slot_history')]
class SlotHistory
{
    const ACTION_CREATE   = 'CREATE';
    const ACTION_UPDATE   = 'UPDATE';
    const ACTION_DELETE   = 'DELETE';
    const ACTION_RESERVE  = 'RESERVE';
    const ACTION_CANCEL   = 'CANCEL';
    const ACTION_BLOCK    = 'BLOCK';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** The user who performed the action */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id_user', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $action = self::ACTION_CREATE;

    /** Entity type: 'Disponibilite', 'Creneau', 'AvailabilityException' */
    #[ORM\Column(name: 'entity_type', length: 50)]
    private string $entityType = '';

    #[ORM\Column(name: 'entity_id', nullable: true)]
    private ?int $entityId = null;

    /** JSON snapshot of the old state */
    #[ORM\Column(name: 'old_state', type: Types::JSON, nullable: true)]
    private ?array $oldState = null;

    /** JSON snapshot of the new state */
    #[ORM\Column(name: 'new_state', type: Types::JSON, nullable: true)]
    private ?array $newState = null;

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }

    public function getEntityType(): string { return $this->entityType; }
    public function setEntityType(string $entityType): static { $this->entityType = $entityType; return $this; }

    public function getEntityId(): ?int { return $this->entityId; }
    public function setEntityId(?int $entityId): static { $this->entityId = $entityId; return $this; }

    public function getOldState(): ?array { return $this->oldState; }
    public function setOldState(?array $oldState): static { $this->oldState = $oldState; return $this; }

    public function getNewState(): ?array { return $this->newState; }
    public function setNewState(?array $newState): static { $this->newState = $newState; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
