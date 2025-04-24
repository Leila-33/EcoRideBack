<?php

namespace App\Entity;

use App\Repository\ConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Parametre::class, mappedBy: 'configuration', orphanRemoval: true)]
    private Collection $parametres;

    #[ORM\ManyToOne(inversedBy: 'configurations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $user = null;

    public function __construct()
    {
        $this->parametres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $parametre->setConfiguration($this);
        }

        return $this;
    }

    public function removeParametre(Parametre $parametre): static
    {
        if ($this->parametres->removeElement($parametre)) {
            // set the owning side to null (unless already changed)
            if ($parametre->getConfiguration() === $this) {
                $parametre->setConfiguration(null);
            }
        }

        return $this;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }
}
