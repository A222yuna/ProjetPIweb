<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    private ?User $psychologue = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'patient_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La note est obligatoire')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être entre 1 et 5')]
    private int $note = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Le commentaire est obligatoire')]
    #[Assert\Length(min: 5, minMessage: 'Le commentaire doit faire au moins 5 caractères')]
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

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): static
    {
        $this->psychologue = $psychologue;

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

    public function setNote(?int $note): static
    {
        $this->note = $note ?? 1;

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

    #[Assert\Callback]
    public function validateCommentaire(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        $comment = trim((string) ($this->commentaire ?? ''));
        if ($comment == '') {
            return;
        }

        $badwords = [
            'con',
            'connard',
            'connasse',
            'idiot',
            'idiote',
            'imbecile',
            'merde',
            'salaud',
            'stupide',
            'pute',
            'cochon',
            'batard',
            'encule',
            'fdp',
            'badword',
        ];

        foreach ($badwords as $word) {
            $pattern = '/(^|[^[:alnum:]])'.preg_quote($word, '/').'([^[:alnum:]]|$)/iu';
            if (preg_match($pattern, $comment) === 1) {
                $context->buildViolation('Votre commentaire contient des mots inappropries. Merci de reformuler avec un langage respectueux.')
                    ->atPath('commentaire')
                    ->addViolation();

                return;
            }
        }
    }
}
