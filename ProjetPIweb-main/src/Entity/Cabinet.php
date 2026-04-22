<?php

namespace App\Entity;

use App\Repository\CabinetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CabinetRepository::class)]
#[ORM\Table(name: 'cabinet')]
class Cabinet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_cabinet')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L adresse est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'L adresse doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'L adresse ne peut pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9\\s\'\\.-]{5,},\\s*[A-Za-z\\s\'\\.-]{2,}\\s\\d{4,5}$/',
        message: 'Format invalide. Exemple attendu: Route de Gabes, Mednine 4100.'
    )]
    private ?string $adresse = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'La ville doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'La ville ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $ville = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Les horaires ne peuvent pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: '/^([A-Za-z]{3,10}(\\s*[-\\/]\\s*[A-Za-z]{3,10})?\\s+)?([01]\\d|2[0-3]):[0-5]\\d\\s*-\\s*([01]\\d|2[0-3]):[0-5]\\d(\\s*;\\s*([01]\\d|2[0-3]):[0-5]\\d\\s*-\\s*([01]\\d|2[0-3]):[0-5]\\d)*$/',
        message: 'Format horaires invalide. Exemple: 08:00-17:00 ou LUN-VEN 08:00-17:00.'
    )]
    private ?string $horaires = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $valide = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $archive = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?float $reputationScore = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $reputationBadge = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scoreUpdatedAt = null;

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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    /** @return Collection<int, Disponibilite> */
    public function getDisponibilites(): Collection
    {
        return $this->disponibilites;
    }

    public function addDisponibilite(Disponibilite $disponibilite): static
    {
        if (!$this->disponibilites->contains($disponibilite)) {
            $this->disponibilites->add($disponibilite);
            $disponibilite->setCabinet($this);
        }

        return $this;
    }

    public function removeDisponibilite(Disponibilite $disponibilite): static
    {
        if ($this->disponibilites->removeElement($disponibilite)) {
            if ($disponibilite->getCabinet() === $this) {
                $disponibilite->setCabinet(null);
            }
        }

        return $this;
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

    public function getReputationScore(): ?float
    {
        return $this->reputationScore !== null ? (float) $this->reputationScore : null;
    }

    public function setReputationScore(?float $reputationScore): static
    {
        $this->reputationScore = $reputationScore;
        return $this;
    }

    public function getReputationBadge(): ?string
    {
        return $this->reputationBadge;
    }

    public function setReputationBadge(?string $reputationBadge): static
    {
        $this->reputationBadge = $reputationBadge;
        return $this;
    }

    public function getScoreUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->scoreUpdatedAt;
    }

    public function setScoreUpdatedAt(?\DateTimeImmutable $scoreUpdatedAt): static
    {
        $this->scoreUpdatedAt = $scoreUpdatedAt;
        return $this;
    }
}
