<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversation')]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_conversation')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'statut_conversation', length: 50, nullable: true)]
    private ?string $statutConversation = null;

    #[ORM\Column(name: 'archiver_conversation', options: ['default' => false])]
    private bool $archiverConversation = false;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation')]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getStatutConversation(): ?string
    {
        return $this->statutConversation;
    }

    public function setStatutConversation(?string $statutConversation): static
    {
        $this->statutConversation = $statutConversation;

        return $this;
    }

    public function isArchiverConversation(): bool
    {
        return $this->archiverConversation;
    }

    public function setArchiverConversation(bool $archiverConversation): static
    {
        $this->archiverConversation = $archiverConversation;

        return $this;
    }

    /** @return Collection<int, Message> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }
}
