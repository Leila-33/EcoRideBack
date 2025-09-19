<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà enregistré.')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà enregistrée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'adresse email est obligatoire.")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas une adresse email valide.")]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{12,}$/',
        message: 'Le mot de passe doit contenir au moins 12 caractères dont au moins une majuscule, une minuscule, un chiffre et un caractère spécial'
    )]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9]{10}$/',
        message: "Le téléphone n'est pas au bon format.")]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "L'adresse ne doit pas dépasser 255 caractères.")]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de naissance est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message : 'La date de naissance doit être une date valide.')]
    private ?\DateTimeInterface $dateNaissance = null;

    #[Assert\Callback]
    public function validateDateNaissance(ExecutionContextInterface $context)
    {
        if (!$this->dateNaissance) {
            return;
        }
        $today = new \DateTime();
        $age = $today->diff($this->dateNaissance)->y;
        if ($this->dateNaissance >= $today) {
            $context->buildViolation('La date de naissance ne peut pas être aujourd\'hui ou dans le futur.')
            ->atPath('dateNaissance')
            ->addViolation();
        } elseif ($age < 18) {
            $context->buildViolation('L\'âge ne peut doit être supérieur ou égal à 18 ans.')
            ->atPath('dateNaissance')
            ->addViolation();
        }
    }

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\Length(max: 50, maxMessage: 'Le pseudo ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'Le pseudo est obligatoire.')]
    private ?string $pseudo = null;

    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'auteur', orphanRemoval: true)]
    private Collection $avis;

    #[ORM\OneToMany(targetEntity: Voiture::class, mappedBy: 'user', orphanRemoval: true, fetch: 'EAGER')]
    private Collection $voitures;

    #[ORM\ManyToMany(targetEntity: Covoiturage::class, inversedBy: 'users')]
    private Collection $covoiturages;

    #[ORM\Column(length: 255)]
    private ?string $apiToken = null;

    #[ORM\OneToOne(inversedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Credit $credit = null;

    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $operations;

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $reponses;

    /**
     * @var Collection<int, Parametre>
     */
    #[ORM\ManyToMany(targetEntity: Parametre::class, inversedBy: 'users')]
    private Collection $parametres;

    /**
     * @var Collection<int, RoleEntity>
     */
    #[ORM\ManyToMany(targetEntity: RoleEntity::class, cascade: ['persist'], inversedBy: 'users')]
    private Collection $roleEntities;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le chemin de la photo ne doit pas dépasser 255 caractères.')]
    private ?string $photo = null;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(mappedBy: 'chauffeur', targetEntity: Avis::class, orphanRemoval: true)]
    private Collection $avisRecus;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
        $this->voitures = new ArrayCollection();
        $this->covoiturages = new ArrayCollection();
        $this->apiToken = bin2hex(random_bytes(20));
        $this->operations = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->parametres = new ArrayCollection();
        $this->roleEntities = new ArrayCollection();
        $this->avisRecus = new ArrayCollection();
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

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
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
            $avi->setAuteur($this);
        }

        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            // set the owning side to null (unless already changed)
            if ($avi->getAuteur() === $this) {
                $avi->setAuteur(null);
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

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getCredit(): ?Credit
    {
        return $this->credit;
    }

    public function setCredit(Credit $credit): static
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setUser($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getUser() === $this) {
                $operation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setUser($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getUser() === $this) {
                $reponse->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Parametre>
     */
    public function getParametres(): Collection
    {
        return $this->parametres;
    }

    public function addParametre(Parametre $parametre): static
    {
        if (!$this->parametres->contains($parametre)) {
            $this->parametres->add($parametre);
        }

        return $this;
    }

    public function removeParametre(Parametre $parametre): static
    {
        $this->parametres->removeElement($parametre);

        return $this;
    }

    /**
     * @return Collection<int, RoleEntity>
     */
    public function getRoleEntities(): Collection
    {
        return $this->roleEntities;
    }

    public function addRoleEntity(RoleEntity $roleEntity): static
    {
        if (!$this->roleEntities->contains($roleEntity)) {
            $this->roleEntities->add($roleEntity);
        }

        return $this;
    }

    public function removeRoleEntity(RoleEntity $roleEntity): static
    {
        $this->roleEntities->removeElement($roleEntity);

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvisRecus(): Collection
    {
        return $this->avisRecus;
    }

    public function addAvisRecu(Avis $avisRecu): static
    {
        if (!$this->avisRecus->contains($avisRecu)) {
            $this->avisRecus->add($avisRecu);
            $avisRecu->setChauffeur($this);
        }

        return $this;
    }

    public function removeAvisRecu(Avis $avisRecu): static
    {
        if ($this->avisRecus->removeElement($avisRecu)) {
            // set the owning side to null (unless already changed)
            if ($avisRecu->getChauffeur() === $this) {
                $avisRecu->setChauffeur(null);
            }
        }

        return $this;
    }
}
