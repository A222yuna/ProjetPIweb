<?php

namespace App\Entity;

use App\Repository\AvailabilityExceptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a blocked period (absence, holiday, etc.) for a cabinet.
 * These periods take priority over all regular slots.
 */
#[ORM\Entity(repositoryClass: AvailabilityExceptionRepository::class)]
#[ORM\Table(name: 'availability_exception')]
class AvailabilityException
{
    const TYPE_ABSENCE  = 'ABSENCE';
    const TYPE_CONGE    = 'CONGE';
    const TYPE_BLOCAGE  = 'BLOCAGE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'cabinet_id', referencedColumnName: 'id_cabinet', nullable: false, onDelete: 'CASCADE')]
    private ?Cabinet $cabinet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'psychologue_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $psychologue = null;

    /** Start date+time of the blocked period */
    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    /** End date+time of the blocked period */
    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_BLOCAGE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCabinet(): ?Cabinet { return $this->cabinet; }
    public function setCabinet(?Cabinet $cabinet): static { $this->cabinet = $cabinet; return $this; }

    public function getPsychologue(): ?User { return $this->psychologue; }
    public function setPsychologue(?User $psychologue): static { $this->psychologue = $psychologue; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(?string $motif): static { $this->motif = $motif; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
