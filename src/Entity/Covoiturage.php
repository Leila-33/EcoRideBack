<?php

namespace App\Entity;

use App\Repository\CovoiturageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CovoiturageRepository::class)]
class Covoiturage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de départ est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date de départ doit être une date valide.')]
    #[Assert\GreaterThan('today', message: "La date de départ doit être postérieure à aujourd'hui.")]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "L'heure de départ est obligatoire.")]
    #[Assert\Time(withSeconds: false, message : "L'heure de départ doit être une heure valide.")]
    private ?string $heureDepart = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'Le lieu de départ ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'Le lieu de départ est obligatoire.')]
    private ?string $lieuDepart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date d'arrivée est obligatoire.")]
    #[Assert\Type(type: \DateTimeInterface::class, message : "La date d'arrivée doit être une date valide.")]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "L'heure d'arrivée est obligatoire.")]
    #[Assert\Time(withSeconds: false, message : "L'heure d'arrivée doit être une heure valide.")]
    private ?string $heureArrivee = null;

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context, $payload)
    {
        if (!$this->dateDepart || !$this->dateArrivee || !$this->heureDepart || !$this->heureArrivee) {
            return;
        }
        $depart = new \DateTime($this->dateDepart->format('Y-m-d').' '.$this->heureDepart);
        $arrivee = new \DateTime($this->dateArrivee->format('Y-m-d').' '.$this->heureArrivee);
        if ($depart > $arrivee) {
            $context->buildViolation("La date d'arrivée doit être égale ou postérieure à la date de départ.
    Si les dates sont identiques, l'heure d'arrivée doit être postérieure à l'heure de départ.")->atPath('heureArrivee')->addViolation();
        }
    }

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: "Le lieu d'arrivée ne doit pas dépasser 50 caractères.")]
    #[Assert\NotBlank(message: "Le lieu d'arrivée est obligatoire.")]
    private ?string $lieuArrivee = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(['en attente', 'en cours', 'terminé'], message: "Le statut doit être 'en attente','en cours' ou 'terminé'.")]
    private ?string $statut = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de places est obligatoire.')]
    #[Assert\Range(min: 1, max: 6, notInRangeMessage: 'Le nombre de places doit être compris entre {{ min }} et {{ max }}.')]
    private ?int $nbPlaces = null;

    #[ORM\ManyToOne(inversedBy: 'covoiturages', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voiture $voiture = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'covoiturages', fetch: 'EAGER')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'covoiturage', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private Collection $reponses;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\Range(min: 3, max: 999.99, notInRangeMessage: 'Le prix doit être compris entre {{ min }} et {{ max }}.')]
    private ?string $prixPersonne = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(mappedBy: 'covoiturage', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $commentaires;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): static
    {
        $this->dateDepart = $dateDepart;

        return $this;
    }

    public function getHeureDepart(): ?string
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(string $heureDepart): static
    {
        $this->heureDepart = $heureDepart;

        return $this;
    }

    public function getLieuDepart(): ?string
    {
        return $this->lieuDepart;
    }

    public function setLieuDepart(string $lieuDepart): static
    {
        $this->lieuDepart = $lieuDepart;

        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(\DateTimeInterface $dateArrivee): static
    {
        $this->dateArrivee = $dateArrivee;

        return $this;
    }

    public function getHeureArrivee(): ?string
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(string $heureArrivee): static
    {
        $this->heureArrivee = $heureArrivee;

        return $this;
    }

    public function getLieuArrivee(): ?string
    {
        return $this->lieuArrivee;
    }

    public function setLieuArrivee(string $lieuArrivee): static
    {
        $this->lieuArrivee = $lieuArrivee;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

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

    public function getVoiture(): ?Voiture
    {
        return $this->voiture;
    }

    public function setVoiture(?Voiture $voiture): static
    {
        $this->voiture = $voiture;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addCovoiturage($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeCovoiturage($this);
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
            $reponse->setCovoiturage($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getCovoiturage() === $this) {
                $reponse->setCovoiturage(null);
            }
        }

        return $this;
    }

    public function getPrixPersonne(): ?string
    {
        return $this->prixPersonne;
    }

    public function setPrixPersonne(string $prixPersonne): static
    {
        $this->prixPersonne = $prixPersonne;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setCovoiturage($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getCovoiturage() === $this) {
                $commentaire->setCovoiturage(null);
            }
        }

        return $this;
    }
}
