<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[Assert\NotBlank(message: 'La réponse est obligatoire.')]
    #[Assert\Choice(['oui', 'non'], message: 'La réponse doit être oui ou non.')]
    #[ORM\Column(length: 255)]
    private ?string $reponse1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Choice(['oui'], message: 'La réponse doit être oui.')]
    private ?string $reponse2 = null;

    #[ORM\ManyToOne(inversedBy: 'reponses', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reponses', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Covoiturage $covoiturage = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(['en attente', 'résolu'], message: "Le statut doit être 'en attente' ou 'résolu'.")]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReponse1(): ?string
    {
        return $this->reponse1;
    }

    public function setReponse1(string $reponse1): static
    {
        $this->reponse1 = $reponse1;

        return $this;
    }

    public function getReponse2(): ?string
    {
        return $this->reponse2;
    }

    public function setReponse2(?string $reponse2): static
    {
        $this->reponse2 = $reponse2;

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

    public function getCovoiturage(): ?Covoiturage
    {
        return $this->covoiturage;
    }

    public function setCovoiturage(?Covoiturage $covoiturage): static
    {
        $this->covoiturage = $covoiturage;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
