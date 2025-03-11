<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['artist.read', 'artist.obra.read']],
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Artist implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['artist.read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['artist.read'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['artist.read'])]
    private ?int $birthYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email()]
    #[Groups(['artist.read'])]
    private ?string $email = null;

    #[ORM\Column(length: 16, unique: true)]
    #[Groups(['artist.read'])]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['artist.read'])]
    private ?string $instagram = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['artist.read'])]
    private ?string $bio = null;

    /**
     * @var Collection<int, Obra>
     */
    #[ORM\OneToMany(targetEntity: Obra::class, mappedBy: 'artist', orphanRemoval: true)]
    #[Groups(['artist.obra.read'])]
    private Collection $obras;

    #[ORM\Column(nullable: true)]
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

    public function getBirthYear(): ?int
    {
        return $this->birthYear;
    }

    public function setBirthYear(?int $birthYear): static
    {
        $this->birthYear = $birthYear;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): static
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

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
            $obra->setArtist($this);
        }

        return $this;
    }

    public function removeObra(Obra $obra): static
    {
        if ($this->obras->removeElement($obra)) {
            $this->obraCount--;
            // set the owning side to null (unless already changed)
            if ($obra->getArtist() === $this) {
                $obra->setArtist(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName()??$this->getId();
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
