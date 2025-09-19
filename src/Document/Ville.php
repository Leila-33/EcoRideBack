<?php

namespace App\Document;

use App\Repository\VilleRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'villes', repositoryClass: VilleRepository::class)]
#[ODM\Index(keys: ['location' => '2dsphere'])]

class Ville
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private ?string $nom = null;

    #[ODM\Field(type: 'string')]
    private ?string $nom_normalise = null;

    #[ODM\Field(type: 'collection')]
    private array $location = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getNomNormalise(): ?string
    {
        return $this->nom_normalise;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getLocation(): array
    {
        return $this->location;
    }

    public function setLocation(float $longitude, float $latitude): self
    {
        $this->location = ['type' => 'Point',
            'coordinates' => [$longitude, $latitude], ];

        return $this;
    }
}
