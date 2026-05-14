<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_post')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'auteur_id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $auteur = null;

    #[ORM\Column(name: 'auteur_role', length: 20)]
    private ?string $auteurRole = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide.")]
    #[Assert\Length(
        min: 3,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères."
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit faire au moins {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column(length: 100)]
    private ?string $categorie = null;

    #[ORM\Column(name: 'nb_likes', options: ['default' => 0])]
    private int $nbLikes = 0;

    #[ORM\Column(name: 'nb_views', options: ['default' => 0])]
    private int $nbViews = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: "image_url", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'is_hidden', options: ['default' => 0])]
    private bool $isHidden = false;

    #[ORM\Column(name: 'is_anonymous', options: ['default' => false])]
    private bool $isAnonymous = false;

    #[ORM\Column(name: 'hidden_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $hiddenAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'hidden_by_id_user', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    private ?User $hiddenBy = null;

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'post', orphanRemoval: true)]
    #[ORM\OrderBy(['date' => 'ASC'])]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getAuteurRole(): ?string
    {
        return $this->auteurRole;
    }

    public function setAuteurRole(string $auteurRole): static
    {
        $this->auteurRole = $auteurRole;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getNbLikes(): int { return $this->nbLikes; }
    public function setNbLikes(int $nbLikes): static { $this->nbLikes = $nbLikes; return $this; }
    public function getNbViews(): int { return $this->nbViews; }
    public function setNbViews(int $nbViews): static { $this->nbViews = $nbViews; return $this; }
    public function incrementViews(): static { $this->nbViews++; return $this; }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): static
    {
        $this->isHidden = $isHidden;
        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): static
    {
        $this->isAnonymous = $isAnonymous;
        return $this;
    }

    public function getHiddenAt(): ?\DateTimeInterface
    {
        return $this->hiddenAt;
    }

    public function setHiddenAt(?\DateTimeInterface $hiddenAt): static
    {
        $this->hiddenAt = $hiddenAt;
        return $this;
    }

    public function getHiddenBy(): ?User
    {
        return $this->hiddenBy;
    }

    public function setHiddenBy(?User $hiddenBy): static
    {
        $this->hiddenBy = $hiddenBy;
        return $this;
    }

    public function isLikedByUser(User $user): bool
    {
        // Comme nous n'avons pas la table de jointure, on renvoie false par défaut
        // Cela évitera l'erreur SQL
        return false;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    /** @return Collection<int, Commentaire> */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }
}
