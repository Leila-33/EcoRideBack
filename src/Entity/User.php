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
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $date_naissance = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $photo = null;

    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    private Collection $role;

    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $avis;

    #[ORM\OneToMany(targetEntity: Voiture::class, mappedBy: 'user', orphanRemoval: true, fetch:'EAGER')]
    private Collection $voitures;

    #[ORM\ManyToMany(targetEntity: Covoiturage::class, inversedBy: 'users')]
    private Collection $covoiturages;
    #[MaxDepth(1)]
    #[ORM\OneToMany(targetEntity: Configuration::class, mappedBy: 'user', orphanRemoval: true, fetch:'EAGER')]
    private Collection $configurations;

    #[ORM\Column(length: 255)]
    private ?string $apiToken = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $photoMime = null;

 
    public function __construct()
    {
        $this->role = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->voitures = new ArrayCollection();
        $this->covoiturages = new ArrayCollection();
        $this->configurations = new ArrayCollection();
        $this->apiToken=bin2hex(random_bytes(20));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getDateNaissance(): ?string
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?string $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    /*public function getPhoto()
    {
        return $this->photo;
    }*/
    public function getPhoto(): ?string
    {
        if (!is_resource($this->photo)){
        return null;}
        rewind($this->photo);
        return base64_encode(stream_get_contents($this->photo));
    }
    public function setPhoto($photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * @return Collection<int, role>
     */
    public function getRole(): Collection
    {
        return $this->role;
    }

    public function addRole(role $role): static
    {
        if (!$this->role->contains($role)) {
            $this->role->add($role);
        }

        return $this;
    }

    public function removeRole(role $role): static
    {
        $this->role->removeElement($role);

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvi(Avis $avi): static
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setUser($this);
        }

        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            // set the owning side to null (unless already changed)
            if ($avi->getUser() === $this) {
                $avi->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Voiture>
     */
    public function getVoitures(): Collection
    {
        return $this->voitures;
    }

    public function addVoiture(Voiture $voiture): static
    {
        if (!$this->voitures->contains($voiture)) {
            $this->voitures->add($voiture);
            $voiture->setUser($this);
        }

        return $this;
    }

    public function removeVoiture(Voiture $voiture): static
    {
        if ($this->voitures->removeElement($voiture)) {
            // set the owning side to null (unless already changed)
            if ($voiture->getUser() === $this) {
                $voiture->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, covoiturage>
     */
    public function getCovoiturages(): Collection
    {
        return $this->covoiturages;
    }

    public function addCovoiturage(covoiturage $covoiturage): static
    {
        if (!$this->covoiturages->contains($covoiturage)) {
            $this->covoiturages->add($covoiturage);
        }

        return $this;
    }

    public function removeCovoiturage(covoiturage $covoiturage): static
    {
        $this->covoiturages->removeElement($covoiturage);

        return $this;
    }

    /**
     * @return Collection<int, Configuration>
     */
    public function getConfigurations(): Collection
    {
        return $this->configurations;
    }

    public function addConfiguration(Configuration $configuration): static
    {
        if (!$this->configurations->contains($configuration)) {
            $this->configurations->add($configuration);
            $configuration->setUser($this);
        }

        return $this;
    }

    public function removeConfiguration(Configuration $configuration): static
    {
        if ($this->configurations->removeElement($configuration)) {
            // set the owning side to null (unless already changed)
            if ($configuration->getUser() === $this) {
                $configuration->setUser(null);
            }
        }

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getPhotoMime(): ?string
    {
        return $this->photoMime;
    }

    public function setPhotoMime(?string $photoMime): static
    {
        $this->photoMime = $photoMime;

        return $this;
    }



}
