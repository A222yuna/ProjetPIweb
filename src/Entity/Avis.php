<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Table(name: 'avis')]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idAvis')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'idProgramme', referencedColumnName: 'idProgramme', nullable: false, onDelete: 'CASCADE')]
    private ?ProgrammeBienEtre $programme = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'psychologue_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\Column]
    private int $note = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'dateAvis', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAvis = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramme(): ?ProgrammeBienEtre
    {
        return $this->programme;
    }

    public function setProgramme(?ProgrammeBienEtre $programme): static
    {
        $this->programme = $programme;

        return $this;
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

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getDateAvis(): ?\DateTimeInterface
    {
        return $this->dateAvis;
    }

    public function setDateAvis(?\DateTimeInterface $dateAvis): static
    {
        $this->dateAvis = $dateAvis;

        return $this;
    }
}
