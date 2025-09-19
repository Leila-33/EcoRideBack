<?php

namespace App\Entity;

use App\Repository\VoitureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoitureRepository::class)]
#[UniqueEntity(fields: ['immatriculation'], message: 'Cette voiture est déjà enregistrée.')]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'Le modèle ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'Le modèle est obligatoire.')]
    private ?string $modele = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\Length(max: 50, maxMessage: "L'immatriculation ne doit pas dépasser 50 caractères.")]
    #[Assert\NotBlank(message: "La plaque d'immatriculation est obligatoire.")]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "L'énergie est obligatoire.")]
    #[Assert\Choice(['Essence', 'Electrique'], message: "L'énergie doit être de l'essence ou électrique.")]
    private ?string $energie = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'La couleur ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'La couleur est obligatoire.')]
    private ?string $couleur = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de première immatriculation est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message : 'La date de première immatriculation doit être une date valide.')]
    #[Assert\LessThanOrEqual('today', message: "La date d'immatriculation ne peut pas être dans le futur.")]
    #[Assert\GreaterThanOrEqual('1900-01-01', message: "La date d'immatriculation doit être postérieure à 1900.")]
    private ?\DateTimeInterface $datePremiereImmatriculation = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de places est obligatoire.')]
    #[Assert\Range(min: 1, max: 6, notInRangeMessage: 'Le nombre de places doit être compris entre {{ min }} et {{ max }}.')]
    private ?int $nbPlaces = null;

    #[ORM\OneToMany(targetEntity: Covoiturage::class, mappedBy: 'voiture', orphanRemoval: true)]
    private Collection $covoiturages;

    #[ORM\ManyToOne(inversedBy: 'voitures', fetch: 'EAGER')]
    #[Assert\Valid]
    #[ORM\JoinColumn(nullable: false)]
    private ?Marque $marque = null;

    #[ORM\ManyToOne(inversedBy: 'voitures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->covoiturages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): static
    {
        $this->energie = $energie;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getDatePremiereImmatriculation(): ?\DateTimeInterface
    {
        return $this->datePremiereImmatriculation;
    }

    public function setDatePremiereImmatriculation(\DateTimeInterface $datePremiereImmatriculation): static
    {
        $this->datePremiereImmatriculation = $datePremiereImmatriculation;

        return $this;
    }

    public function getNbPlaces(): ?int
    {
        return $this->nbPlaces;
    }

    public function setNbPlaces(int $nbPlaces): static
    {
        $this->nbPlaces = $nbPlaces;

        return $this;
    }

    /**
     * @return Collection<int, Covoiturage>
     */
    public function getCovoiturages(): Collection
    {
        return $this->covoiturages;
    }

    public function addCovoiturage(Covoiturage $covoiturage): static
    {
        if (!$this->covoiturages->contains($covoiturage)) {
            $this->covoiturages->add($covoiturage);
            $covoiturage->setVoiture($this);
        }

        return $this;
    }

    public function removeCovoiturage(Covoiturage $covoiturage): static
    {
        if ($this->covoiturages->removeElement($covoiturage)) {
            // set the owning side to null (unless already changed)
            if ($covoiturage->getVoiture() === $this) {
                $covoiturage->setVoiture(null);
            }
        }

        return $this;
    }

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
