<?php

namespace App\Entity;

use App\Repository\CreditRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CreditRepository::class)]
class Credit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'credit', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'La valeur du total est obligatoire.')]
    #[Assert\GreaterThanOrEqual(0, message: 'Le total doit être un nombre supérieur ou égal à 0.')]
    private ?string $total = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        if ($user->getCredit() !== $this) {
            $user->setCredit($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function addCredit(float $amount): static
    {
        $newTotal = $this->total + round($amount, 2);
        if ($newTotal < 0) {
            throw new \InvalidArgumentException('Le crédit ne peut être négatif.');
        }
        $this->total = (string) $newTotal;

        return $this;
    }
}
