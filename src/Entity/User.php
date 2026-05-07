<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'mot_de_passe', length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 20)]
    private string $role = 'Patient';

    #[ORM\Column(name: 'date_inscription', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: 'est_actif', options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'email_verifie', options: ['default' => false])]
    private bool $emailVerifie = false;

    #[ORM\Column(name: 'derniere_connexion', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $derniereConnexion = null;

    #[ORM\Column(name: 'statut_validation', length: 20, options: ['default' => 'approuve'])]
    private string $statutValidation = 'approuve';

    #[ORM\Column(name: 'photo_profil', length: 255, nullable: true)]
    private ?string $photoProfil = null;

    /** @var Collection<int, Post> */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'auteur')]
    private Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;

        return $this;
    }

    public function isEmailVerifie(): bool
    {
        return $this->emailVerifie;
    }

    public function setEmailVerifie(bool $emailVerifie): static
    {
        $this->emailVerifie = $emailVerifie;

        return $this;
    }

    public function getDerniereConnexion(): ?\DateTimeInterface
    {
        return $this->derniereConnexion;
    }

    public function setDerniereConnexion(?\DateTimeInterface $derniereConnexion): static
    {
        $this->derniereConnexion = $derniereConnexion;

        return $this;
    }

    public function getStatutValidation(): string
    {
        return $this->statutValidation;
    }

    public function setStatutValidation(string $statutValidation): static
    {
        $this->statutValidation = $statutValidation;

        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): static
    {
        $this->photoProfil = $photoProfil;

        return $this;
    }

    /** @return Collection<int, Post> */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = match ($this->role) {
            'Admin' => ['ROLE_ADMIN'],
            'Psychologue' => ['ROLE_PSYCHOLOGUE'],
            default => ['ROLE_PATIENT'],
        };
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getPassword(): string
    {
        return $this->motDePasse ?? '';
    }

    public function eraseCredentials(): void
    {
    }
}
