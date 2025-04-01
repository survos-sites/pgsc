<?php

namespace App\Entity;

use AlexandreFernandez\JsonTranslationBundle\Doctrine\Type\JsonTranslationType;
use AlexandreFernandez\JsonTranslationBundle\Model\JsonTranslation;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
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
class Artist implements \Stringable, RouteParametersInterface
{
    use RouteParametersTrait;

    public const array UNIQUE_PARAMETERS = ['artistId' => 'id'];

    // make this an ENUM?
    public const STUDIO_VISITABLE = [
        'studio.open',
        'studio.appointment',
        'studio.closed',
    ];

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

//    #[ORM\Column(type: Types::TEXT, nullable: true)]
//    #[Groups(['artist.read'])]
//    private ?string $textBio = null;

    #[ORM\Column(type: JsonTranslationType::TYPE, nullable: true)]
    #[Groups(['artist.read'])]
    private ?JsonTranslation $bio = null;

    /**
     * @var Collection<int, Obra>
     */
    #[ORM\OneToMany(targetEntity: Obra::class, mappedBy: 'artist', orphanRemoval: true)]
    #[Groups(['artist.obra.read'])]
    private Collection $obras;

    #[ORM\Column(nullable: true)]
    private int $obraCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $socialMedia = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $studioAddress = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $studioVisitable = null;

    public function __construct()
    {
        $this->obras = new ArrayCollection();
        $this->bio = new JsonTranslation();
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

    public function getBio(): ?JsonTranslation
    {
        return $this->bio;
    }

    public function setBio(?JsonTranslation $bio): static
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
        return $this->getName() ?? $this->getId();
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

    public function getSocialMedia(): ?string
    {
        return $this->socialMedia;
    }

    public function setSocialMedia(?string $socialMedia): static
    {
        $this->socialMedia = $socialMedia;

        return $this;
    }

    public function getStudioAddress(): ?string
    {
        return $this->studioAddress;
    }

    public function setStudioAddress(?string $studioAddress): static
    {
        $this->studioAddress = $studioAddress;

        return $this;
    }

    public function getStudioVisitable(): ?string
    {
        return $this->studioVisitable;
    }

    public function setStudioVisitable(?string $studioVisitable): static
    {
        $this->studioVisitable = $studioVisitable;

        return $this;
    }
}
