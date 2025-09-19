<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date d'opération est obligatoire.")]
    #[Assert\Type(type: \DateTimeInterface::class, message : "La date d'opération doit être une date valide.")]
    private ?\DateTimeInterface $dateOperation = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull(message: 'La valeur du montant est obligatoire.')]
    #[Assert\Range(min: 3, max: 999.99, notInRangeMessage: 'La valeur du montant doit être compris entre {{ min }} et {{ max }}.')]
    private ?string $montant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateOperation(): ?\DateTimeInterface
    {
        return $this->dateOperation;
    }

    public function setDateOperation(\DateTimeInterface $dateOperation): static
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

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
