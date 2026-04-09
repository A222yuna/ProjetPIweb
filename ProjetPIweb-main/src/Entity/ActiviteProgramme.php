<?php

namespace App\Entity;

use App\Repository\ActiviteProgrammeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActiviteProgrammeRepository::class)]
#[ORM\Table(name: 'activite_programme')]
class ActiviteProgramme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idActivite')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'activites')]
    #[ORM\JoinColumn(name: 'idProgramme', referencedColumnName: 'idProgramme', nullable: false, onDelete: 'CASCADE')]
    private ?ProgrammeBienEtre $programme = null;

    #[ORM\Column]
    private int $jour = 0;

    #[ORM\Column(name: 'heureDebut', type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'dureeMinutes', nullable: true)]
    private ?int $dureeMinutes = null;

    #[ORM\Column(name: 'typeActivite', length: 100, nullable: true)]
    private ?string $typeActivite = null;

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

    public function getJour(): int
    {
        return $this->jour;
    }

    public function setJour(int $jour): static
    {
        $this->jour = $jour;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDureeMinutes(): ?int
    {
        return $this->dureeMinutes;
    }

    public function setDureeMinutes(?int $dureeMinutes): static
    {
        $this->dureeMinutes = $dureeMinutes;

        return $this;
    }

    public function getTypeActivite(): ?string
    {
        return $this->typeActivite;
    }

    public function setTypeActivite(?string $typeActivite): static
    {
        $this->typeActivite = $typeActivite;

        return $this;
    }
}
