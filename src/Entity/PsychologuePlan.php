<?php

namespace App\Entity;

use App\Repository\PsychologuePlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PsychologuePlanRepository::class)]
#[ORM\Table(name: 'psychologue_plans')]
class PsychologuePlan
{
    public const DAY_OF_WEEK_CHOICES = [
        'MONDAY',
        'TUESDAY',
        'WEDNESDAY',
        'THURSDAY',
        'FRIDAY',
        'SATURDAY',
        'SUNDAY',
    ];

    public const PERIOD_CHOICES = ['DAY', 'NIGHT'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'psychologue_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $psychologue = null;

    #[ORM\Column(name: 'day_of_week', length: 15)]
    #[Assert\NotBlank(message: 'Le jour est obligatoire')]
    #[Assert\Choice(choices: self::DAY_OF_WEEK_CHOICES, message: 'Jour invalide')]
    private ?string $dayOfWeek = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'La période est obligatoire')]
    #[Assert\Choice(choices: self::PERIOD_CHOICES, message: 'Période invalide')]
    private ?string $period = null;

    #[ORM\Column(name: 'max_appointments', options: ['default' => 5])]
    #[Assert\NotBlank(message: 'Le nombre max est obligatoire')]
    #[Assert\Positive(message: 'Doit être un nombre positif')]
    #[Assert\Range(min: 1, max: 20, notInRangeMessage: 'Entre 1 et 20 rendez-vous maximum')]
    private int $maxAppointments = 5;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, Appointment> */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'plan')]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): static
    {
        $this->psychologue = $psychologue;

        return $this;
    }

    public function getDayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(string $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getMaxAppointments(): int
    {
        return $this->maxAppointments;
    }

    public function setMaxAppointments(int $maxAppointments): static
    {
        $this->maxAppointments = $maxAppointments;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, Appointment> */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }
}
