<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['location.obra.read', 'location.read']],
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Location implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['obra.location.read','location.read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['location.read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['location.read'])]
    private ?string $address = null;

    /**
     * @var Collection<int, Obra>
     */
    #[ORM\OneToMany(targetEntity: Obra::class, mappedBy: 'location')]
    private Collection $obras;

    #[ORM\Column(length: 255)]
    #[Groups(['location.read','obra.location.read'])]
    private ?string $code = null;

    #[ORM\Column(length: 24, nullable: true)]
    #[Groups(['location.read'])]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['location.read'])]
    private int $obraCount = 0;

    public function __construct()
    {
        $this->obras = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, Obra>
     */
    public function getObras(): Collection
    {
        return $this->obras;
    }

    public function addObra(Obra $obra): static
    {
        if (!$this->obras->contains($obra)) {
            $this->obraCount++;
            $this->obras->add($obra);
            $obra->setLocation($this);
        }

        return $this;
    }

    public function removeObra(Obra $obra): static
    {
        if ($this->obras->removeElement($obra)) {
            $this->obraCount--;
            // set the owning side to null (unless already changed)
            if ($obra->getLocation() === $this) {
                $obra->setLocation(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getObraCount(): ?int
    {
        return $this->obraCount;
    }

    public function setObraCount(?int $obraCount): static
    {
        $this->obraCount = $obraCount;

        return $this;
    }
}
