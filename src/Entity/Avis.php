<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'La note est obligatoire.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.')]
    private ?int $note = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(['en attente', 'validé'], message: "Le statut doit être 'en attente' ou 'validé'.")]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le commentaire ne doit pas dépasser 255 caractères.')]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'avisRecus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $chauffeur = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

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

    /**
     * @return Collection<int, User>
     */
    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?user $user): static
    {
        $this->auteur = $user;

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

    public function getChauffeur(): ?User
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?User $chauffeur): static
    {
        $this->chauffeur = $chauffeur;

        return $this;
    }
}
