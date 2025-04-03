<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ObraRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ObraRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['obra.read', 'obra.location.read']]
)]
class Obra implements \Stringable, RouteParametersInterface
{
    use RouteParametersTrait;
    public const array UNIQUE_PARAMETERS = ['obraId' => 'id'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['obra.read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['obra.read'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'obras')]
    #[Groups(['obra.read'])]
    private ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'obras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $artist = null;

    #[ORM\Column(length: 255)]
    #[Groups(['artist.obra.read', 'obra.read'])]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    #[ORM\Column(nullable: true)]
    private ?int $depth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $materials = null;

    /**
     * @var Collection<int, ObraImage>
     */
    #[ORM\OneToMany(targetEntity: ObraImage::class, mappedBy: 'obra', cascade: ['persist'], orphanRemoval: true)]
    private Collection $obraImages;

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $type = null;

    public function __construct()
    {
        $this->obraImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): static
    {
        $this->artist = $artist;

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

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getDepth(): ?int
    {
        return $this->depth;
    }

    public function setDepth(?int $depth): static
    {
        $this->depth = $depth;

        return $this;
    }

    public function getMaterials(): ?string
    {
        return $this->materials;
    }

    public function setMaterials(?string $materials): static
    {
        $this->materials = $materials;

        return $this;
    }

    /**
     * @return Collection<int, ObraImage>
     */
    public function getObraImages(): Collection
    {
        return $this->obraImages;
    }

    public function addObraImage(ObraImage $obraImage): static
    {
        if (!$this->obraImages->contains($obraImage)) {
            $this->obraImages->add($obraImage);
            $obraImage->setObra($this);
        }

        return $this;
    }

    public function removeObraImage(ObraImage $obraImage): static
    {
        if ($this->obraImages->removeElement($obraImage)) {
            // set the owning side to null (unless already changed)
            if ($obraImage->getObra() === $this) {
                $obraImage->setObra(null);
            }
        }

        return $this;
    }

    public function getDimensions(): string
    {
        $dimensions = sprintf('%dcm x %dcm', $this->width ?? 0, $this->height ?? 0);

        if ($this->depth) {
            $dimensions = sprintf('%s x %dcm', $dimensions, $this->depth);
        }

        return $dimensions;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = $price;

        return $this;
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
}
