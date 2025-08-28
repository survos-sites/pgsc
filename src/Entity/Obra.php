<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\MediaFieldsTrait;
use App\Repository\ObraRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\BabelBundle\Attribute\BabelStorage;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ObraRepository::class)]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
    normalizationContext: ['groups' => ['obra.read', 'translatable', 'obra.location.read', 'obra.artist.read', 'media.read']]
)]
#[BabelStorage]
class Obra implements \Stringable, RouteParametersInterface
{
    use RouteParametersTrait;
    use MediaFieldsTrait;

    public const array UNIQUE_PARAMETERS = ['obraId' => 'code'];

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        #[Groups(['obra.read'])]
        public string $code,
    ) {
        $this->initMediaCollections();
    }
    public string $id { get => $this->code; }

    #[Groups(['obra.read'])]
    public ?Media $image { get => $this->images->first() ? $this->images->first() : null; }


// AFTER — reference code, and name the FK columns
    #[ORM\ManyToOne(inversedBy: 'obras')]
    #[ORM\JoinColumn(name: 'location_code', referencedColumnName: 'code', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['obra.read', 'obra.location.read'])]
    public ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'obras')]
    #[ORM\JoinColumn(name: 'artist_code', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['obra.read','obra.artist.read'])]
    public ?Artist $artist = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['obra.read'])]
    public ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['obra.read'])]
    public ?string $description = null;

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

    public function __toString(): string
    {
        return $this->title ?? $this->code;
    }

    #[Groups(['obra.read'])]
    public function getDimensions(): string
    {
        $w = $this->width ?? 0;
        $h = $this->height ?? 0;
        $d = $this->depth;

        return $d !== null ? sprintf('%dcm x %dcm x %dcm', $w, $h, $d) : sprintf('%dcm x %dcm', $w, $h);
    }
}
