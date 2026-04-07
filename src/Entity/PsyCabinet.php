<?php

namespace App\Entity;

use App\Repository\PsyCabinetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PsyCabinetRepository::class)]
#[ORM\Table(name: 'psy_cabinet')]
#[ORM\UniqueConstraint(name: 'uq_psy_cabinet', columns: ['psychologue_id_user', 'id_cabinet'])]
class PsyCabinet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_psy_cabinet')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'psychologue_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $psychologue = null;

    #[ORM\ManyToOne(inversedBy: 'psyCabinets')]
    #[ORM\JoinColumn(name: 'id_cabinet', referencedColumnName: 'id_cabinet', nullable: false, onDelete: 'CASCADE')]
    private ?Cabinet $cabinet = null;

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

    public function getCabinet(): ?Cabinet
    {
        return $this->cabinet;
    }

    public function setCabinet(?Cabinet $cabinet): static
    {
        $this->cabinet = $cabinet;

        return $this;
    }
}
