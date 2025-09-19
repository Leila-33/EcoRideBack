<?php

namespace App\Entity;

use App\Repository\ParametreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParametreRepository::class)]
class Parametre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'La propriété ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'La propriété est obligatoire.')]
    private ?string $propriete = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50, maxMessage: 'La valeur ne doit pas dépasser 50 caractères.')]
    #[Assert\NotBlank(message: 'La valeur est obligatoire.')]
    private ?string $valeur = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'parametres')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropriete(): ?string
    {
        return $this->propriete;
    }

    public function setPropriete(string $propriete): static
    {
        $this->propriete = $propriete;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;

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
            $user->addParametre($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeParametre($this);
        }

        return $this;
    }
}
