<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
#[ORM\Table(name: 'disponibilite')]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'disponibilites')]
    #[ORM\JoinColumn(name: 'cabinet_id', referencedColumnName: 'id_cabinet', nullable: false, onDelete: 'CASCADE')]
    private ?Cabinet $cabinet = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'Le jour est obligatoire')]
    #[Assert\Range(min: 1, max: 7, notInRangeMessage: 'Jour invalide')]
    private int $jour = 0;

    #[ORM\Column(name: 'heure_debut', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire")]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: 'heure_fin', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure de fin est obligatoire")]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(name: 'duree_consultation')]
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\Positive(message: 'La durée doit être positive')]
    #[Assert\Range(min: 15, max: 120, notInRangeMessage: 'La durée doit être entre 15 et 120 minutes')]
    private int $dureeConsultation = 0;

    /** @var Collection<int, Creneau> */
    #[ORM\OneToMany(targetEntity: Creneau::class, mappedBy: 'disponibilite')]
    private Collection $creneaux;

    public function __construct()
    {
        $this->creneaux = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getDureeConsultation(): int
    {
        return $this->dureeConsultation;
    }

    public function setDureeConsultation(int $dureeConsultation): static
    {
        $this->dureeConsultation = $dureeConsultation;

        return $this;
    }

    /** @return Collection<int, Creneau> */
    public function getCreneaux(): Collection
    {
        return $this->creneaux;
    }
}
