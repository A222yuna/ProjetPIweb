<?php

namespace App\Entity;

use App\Repository\CreneauRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CreneauRepository::class)]
#[ORM\Table(name: 'creneau')]
class Creneau
{
    public const STATUT_RESERVE = 'RESERVE';
    public const STATUT_ANNULE = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'creneaux')]
    #[ORM\JoinColumn(name: 'disponibilite_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Disponibilite $disponibilite = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'patient_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\Column(name: 'date_creneau', type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\GreaterThanOrEqual(value: 'today', message: 'La date ne peut pas être dans le passé')]
    private ?\DateTimeImmutable $dateCreneau = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotBlank(message: "L'heure est obligatoire")]
    private ?\DateTimeImmutable $heure = null;

    #[ORM\Column(length: 20, options: ['default' => 'RESERVE'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: [self::STATUT_RESERVE, self::STATUT_ANNULE], message: 'Statut invalide')]
    private string $statut = self::STATUT_RESERVE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDisponibilite(): ?Disponibilite
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?Disponibilite $disponibilite): static
    {
        $this->disponibilite = $disponibilite;

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

    public function getDateCreneau(): ?\DateTimeImmutable
    {
        return $this->dateCreneau;
    }

    public function setDateCreneau(\DateTimeImmutable $dateCreneau): static
    {
        $this->dateCreneau = $dateCreneau;

        return $this;
    }

    public function getHeure(): ?\DateTimeImmutable
    {
        return $this->heure;
    }

    public function setHeure(\DateTimeImmutable $heure): static
    {
        $this->heure = $heure;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
