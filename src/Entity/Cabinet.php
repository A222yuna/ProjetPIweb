<?php

namespace App\Entity;

use App\Repository\CabinetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CabinetRepository::class)]
#[ORM\Table(name: 'cabinet')]
class Cabinet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_cabinet')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 100)]
    private ?string $ville = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $horaires = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $valide = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $archive = false;

    /** @var Collection<int, Disponibilite> */
    #[ORM\OneToMany(targetEntity: Disponibilite::class, mappedBy: 'cabinet')]
    private Collection $disponibilites;

    /** @var Collection<int, PsyCabinet> */
    #[ORM\OneToMany(targetEntity: PsyCabinet::class, mappedBy: 'cabinet')]
    private Collection $psyCabinets;

    /** @var Collection<int, Rating> */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'cabinet')]
    private Collection $ratings;

    public function __construct()
    {
        $this->disponibilites = new ArrayCollection();
        $this->psyCabinets = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getHoraires(): ?string
    {
        return $this->horaires;
    }

    public function setHoraires(?string $horaires): static
    {
        $this->horaires = $horaires;

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

    public function isValide(): bool
    {
        return $this->valide;
    }

    public function setValide(bool $valide): static
    {
        $this->valide = $valide;

        return $this;
    }

    public function isArchive(): bool
    {
        return $this->archive;
    }

    public function setArchive(bool $archive): static
    {
        $this->archive = $archive;

        return $this;
    }

    /** @return Collection<int, Disponibilite> */
    public function getDisponibilites(): Collection
    {
        return $this->disponibilites;
    }

    /** @return Collection<int, PsyCabinet> */
    public function getPsyCabinets(): Collection
    {
        return $this->psyCabinets;
    }

    /** @return Collection<int, Rating> */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }
}
