<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SacroRepository;
use App\Workflow\ISacroWorkflow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\BabelBundle\Attribute\BabelLocale;
use Survos\BabelBundle\Attribute\BabelStorage;
use Survos\BabelBundle\Attribute\Translatable;
use Survos\StateBundle\Traits\MarkingInterface;
use Survos\StateBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;


use Survos\BabelBundle\Entity\Traits\BabelHooksTrait;

use Doctrine\ORM\Mapping\Column;
#[ORM\Entity(repositoryClass: SacroRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['sacro.read']],
    operations: [
        new Get(),

        new GetCollection(),
    ]
)]
#[BabelStorage]
class Sacro implements \Stringable, MarkingInterface
{
    use BabelHooksTrait;

    use MarkingTrait;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private ?string $id = null
    ) {
        $this->id = $id;
        $this->marking = ISacroWorkflow::PLACE_NEW;
    }

    #[BabelLocale()]
    public string $locale = 'es';

    #[ORM\Column(length: 255, nullable: true)]
    #[Translatable]
    public ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?array $extra = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $flickrUrl = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $flickrInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $saisId = null;

    #[ORM\Column(nullable: true)]
    #[Groups('sacro.read')]
    private ?array $imageSizes = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $driveUrl = null;

    #[Groups('sacro.read')]
    #[SerializedName('code')]
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    public function addExtra(string $var, mixed $value): static
    {
        $this->extra[$var] = $value;
        return $this;
    }

    public function __toString()
    {
        return $this->label;
    }

//    #[Groups('sacro.read')]
//    public function getNotes(?string $locale = null): ?string
//    {
//        return $this->translate($locale)->getNotes();
//    }
//    #[Groups('sacro.read')]
//    public function getLabel(?string $locale = null): ?string
//    {
//        return $this->translate($locale)->getLabel();
//    }
//
//    #[Groups('sacro.read')]
//    public function getDescription(?string $locale = null): ?string
//    {
//        return $this->translate($locale)->getDescription();
//    }
    public function getFlickrId(): ?string
    {
        if ($url =  $this->extra['flickr'] ?? null) {
            // assumes that the username isn't all numbers
            if (preg_match('|/(\d+)/|', $url, $matches)) {
                return $matches[1];
            } else {
                dd($url);
            }
        }
        return null;
    }

    public function getFlickrUrl(): ?string
    {
        return $this->flickrUrl;
    }

    public function setFlickrUrl(?string $flickrUrl): static
    {
        $this->flickrUrl = $flickrUrl;

        return $this;
    }

    public function getFlickrInfo(): ?array
    {
        return $this->flickrInfo;
    }

    public function setFlickrInfo(?array $flickrInfo): static
    {
        $this->flickrInfo = $flickrInfo;

        return $this;
    }

    public function getSaisId(): ?string
    {
        return $this->saisId;
    }

    public function setSaisId(?string $saisId): static
    {
        $this->saisId = $saisId;

        return $this;
    }

    public function getImageSizes(): ?array
    {
        return $this->imageSizes;
    }

    public function setImageSizes(?array $imageSizes): static
    {
        $this->imageSizes = $imageSizes;

        return $this;
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

        // <BABEL:TRANSLATABLE:START label>
        #[Column(type: Types::STRING, length: 255, nullable: false)]
        private ?string $labelBacking = null;

        #[Translatable(context: NULL)]
        public ?string $label {
            get => $this->resolveTranslatable('label', $this->labelBacking, NULL);
            set => $this->labelBacking = $value;
        }
        // <BABEL:TRANSLATABLE:END label>

        // <BABEL:TRANSLATABLE:START description>
        #[Column(type: Types::TEXT, nullable: true)]
        private ?string $descriptionBacking = null;

        #[Translatable(context: NULL)]
        public ?string $description {
            get => $this->resolveTranslatable('description', $this->descriptionBacking, NULL);
            set => $this->descriptionBacking = $value;
        }
        // <BABEL:TRANSLATABLE:END description>
}
