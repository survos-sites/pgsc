<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Media;
use App\Entity\Traits\ImageCodesTrait;
use App\Repository\ObraRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\BabelBundle\Attribute\BabelStorage;
use Survos\BabelBundle\Attribute\Translatable;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ObraRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['obra.read', 'obra.location.read', 'obra.artist.read', 'media.read']]
)]
#[BabelStorage()]
class Obra implements \Stringable, RouteParametersInterface
{
    use RouteParametersTrait;
    use ImageCodesTrait;
    use MediaFieldsTrait;
    public const array UNIQUE_PARAMETERS = ['obraId' => 'code'];


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['obra.read'])]
    #[Translatable()]
    public ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'obras')]
    #[Groups(['obra.read', 'obra.location.read'])]
    public ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'obras')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['obra.read','obra.artist.read'])]
    public ?Artist $artist = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['obra.read'])]
    public ?int $year = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['obra.read'])]
    public ?int $width = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['obra.read'])]
    public ?int $height = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['obra.read'])]
    public ?int $depth = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['obra.read'])]
    public ?string $materials = null;


    #[ORM\Column(nullable: true)]
    #[Groups(['obra.read'])]
    public ?int $price = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['obra.read'])]
    public ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['obra.read'])]
    public ?string $size = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        #[Groups(['obra.read'])]
        private(set) ?string $code = null,

        #[ORM\Column(length: 255, nullable: true)]
        #[Groups(['obra.read'])]
        public ?string $title = null
    )
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->code;
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
        return $this->title;
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

    public function getAudioCode(): ?string
    {
        return $this->audioCode;
    }

    public function setAudioCode(?string $audioCode): static
    {
        $this->audioCode = $audioCode;

        return $this;
    }

    /**
     * Virtual property to hold the loaded audio Media entity
     * Populated by PopulateImagesListener
     */
    #[Groups(['obra.read'])]
    public ?Media $audio = null;

    /**
     * Get the loaded audio Media entity
     */
    #[Groups(['obra.read'])]
    public function getAudio(): ?Media
    {
        return $this->audio;
    }

    #[Groups(['obra.read'])]
    public function getArtistCode(): ?string
    {
        return $this->getArtist()?->getCode();

    }

    #[Groups(['obra.read'])]
    public function getLocationCode(): ?string
    {
        return $this->getLocation()?->getCode();
    }

    /**
     * Get the artist name - used for template compatibility
     */
    #[Groups(['obra.read'])]
    public function getArtistName(): ?string
    {
        return $this->getArtist()?->getName();
    }

    /**
     * Get content array for multilingual descriptions - used for template compatibility
     */
    #[Groups(['obra.read'])]
    public function getContent(): array
    {
        // For now, return description in multiple locales
        // This could be enhanced to support actual multilingual content
        return [
            'en' => $this->description,
            'es' => $this->description,
            'tzo' => $this->description,
        ];
    }

    public function getDriveUrl(): ?string
    {
        return $this->driveUrl;
    }

    public function setDriveUrl(?string $driveUrl): static
    {
        $this->driveUrl = $driveUrl;

        return $this;
    }

    public function getImageCodes(): ?array
    {
        return $this->imageCodes ?? [];
    }

    public function setImageCodes(?array $imageCodes): static
    {
        $this->imageCodes = $imageCodes;
        return $this;
    }

    public function addImageCode(string $imageCode): static
    {
        $codes = $this->getImageCodes();
        if (!in_array($imageCode, $codes)) {
            $codes[] = $imageCode;
            $this->setImageCodes($codes);
        }
        return $this;
    }

    public function removeImageCode(string $imageCode): static
    {
        $codes = $this->getImageCodes();
        $key = array_search($imageCode, $codes);
        if ($key !== false) {
            unset($codes[$key]);
            $this->setImageCodes(array_values($codes));
        }
        return $this;
    }

    /**
     * Get the primary image code (first in array)
     * Used for main obra image display
     */
    #[Groups(['obra.read'])]
    public function getPrimaryImageCode(): ?string
    {
        $codes = $this->getImageCodes();
        return $codes[0] ?? null;
    }


    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(?string $youtubeUrl): static
    {
        $this->youtubeUrl = $youtubeUrl;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size;

        return $this;
    }
}
