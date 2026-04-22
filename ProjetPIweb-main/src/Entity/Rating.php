<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'rating')]
#[ORM\UniqueConstraint(name: 'uq_patient_cabinet', columns: ['patient_id_user', 'cabinet_id'])]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'patient_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\ManyToOne(inversedBy: 'ratings')]
    #[ORM\JoinColumn(name: 'cabinet_id', referencedColumnName: 'id_cabinet', nullable: false, onDelete: 'CASCADE')]
    private ?Cabinet $cabinet = null;

    #[ORM\Column]
    private int $note = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?float $noteEcoute = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?float $noteCompetence = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?float $notePonctualite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?float $noteEnvironnement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?float $noteGlobale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireRating = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getCabinet(): ?Cabinet
    {
        return $this->cabinet;
    }

    public function setCabinet(?Cabinet $cabinet): static
    {
        $this->cabinet = $cabinet;

        return $this;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getNoteEcoute(): ?float
    {
        return $this->noteEcoute !== null ? (float) $this->noteEcoute : null;
    }

    public function setNoteEcoute(?float $v): static
    {
        $this->noteEcoute = $v;
        return $this;
    }

    public function getNoteCompetence(): ?float
    {
        return $this->noteCompetence !== null ? (float) $this->noteCompetence : null;
    }

    public function setNoteCompetence(?float $v): static
    {
        $this->noteCompetence = $v;
        return $this;
    }

    public function getNotePonctualite(): ?float
    {
        return $this->notePonctualite !== null ? (float) $this->notePonctualite : null;
    }

    public function setNotePonctualite(?float $v): static
    {
        $this->notePonctualite = $v;
        return $this;
    }

    public function getNoteEnvironnement(): ?float
    {
        return $this->noteEnvironnement !== null ? (float) $this->noteEnvironnement : null;
    }

    public function setNoteEnvironnement(?float $v): static
    {
        $this->noteEnvironnement = $v;
        return $this;
    }

    public function getNoteGlobale(): ?float
    {
        return $this->noteGlobale !== null ? (float) $this->noteGlobale : null;
    }

    public function setNoteGlobale(?float $v): static
    {
        $this->noteGlobale = $v;
        return $this;
    }

    public function getCommentaireRating(): ?string
    {
        return $this->commentaireRating;
    }

    public function setCommentaireRating(?string $v): static
    {
        $this->commentaireRating = $v;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $v): static
    {
        $this->isVerified = $v;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $v): static
    {
        $this->createdAt = $v;
        return $this;
    }
}
