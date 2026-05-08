<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
#[ORM\HasLifecycleCallbacks]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_message')]
    private ?int $id = null;

    #[ORM\Column(name: 'contenu_message', type: Types::TEXT)]
    private ?string $contenuMessage = null;

    #[ORM\Column(name: 'date_message', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateMessage = null;

    #[ORM\Column(name: 'est_lu', options: ['default' => false])]
    private bool $estLu = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'expediteur_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $expediteur = null;

    #[ORM\Column(name: 'expediteur_role', length: 20)]
    private ?string $expediteurRole = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'destinataire_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $destinataire = null;

    #[ORM\Column(name: 'destinataire_role', length: 20)]
    private ?string $destinataireRole = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'id_conversation', referencedColumnName: 'id_conversation', nullable: false, onDelete: 'CASCADE')]
    private ?Conversation $conversation = null;
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function handleProfanityFilter(): void
    {
        // Define your list of bad words
        $forbiddenWords = ['fuck', 'asshole', 'bitch', 'shit'];

        if ($this->contenuMessage === null) {
            return;
        }

        $filteredText = $this->contenuMessage;

        foreach ($forbiddenWords as $word) {
            // preg_quote handles special characters in words
            // the 'i' flag makes it case-insensitive
            $pattern = '/' . preg_quote($word, '/') . '/i';
            $filteredText = preg_replace($pattern, '****', $filteredText);
        }

        $this->contenuMessage = $filteredText;
    }
    public function __construct()
    {
        $this->dateMessage = new  \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenuMessage(): ?string
    {
        return $this->contenuMessage;
    }

    public function setContenuMessage(string $contenuMessage): static
    {
        $this->contenuMessage = $contenuMessage;

        return $this;
    }

    public function getDateMessage(): ?\DateTimeInterface
    {
        return $this->dateMessage;
    }

    public function setDateMessage(\DateTimeInterface $dateMessage): static
    {
        $this->dateMessage = $dateMessage;

        return $this;
    }

    public function isEstLu(): bool
    {
        return $this->estLu;
    }

    public function setEstLu(bool $estLu): static
    {
        $this->estLu = $estLu;

        return $this;
    }

    public function getExpediteur(): ?User
    {
        return $this->expediteur;
    }

    public function setExpediteur(?User $expediteur): static
    {
        $this->expediteur = $expediteur;

        return $this;
    }

    public function getExpediteurRole(): ?string
    {
        return $this->expediteurRole;
    }

    public function setExpediteurRole(string $expediteurRole): static
    {
        $this->expediteurRole = $expediteurRole;

        return $this;
    }

    public function getDestinataire(): ?User
    {
        return $this->destinataire;
    }

    public function setDestinataire(?User $destinataire): static
    {
        $this->destinataire = $destinataire;

        return $this;
    }

    public function getDestinataireRole(): ?string
    {
        return $this->destinataireRole;
    }

    public function setDestinataireRole(string $destinataireRole): static
    {
        $this->destinataireRole = $destinataireRole;

        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }
}
