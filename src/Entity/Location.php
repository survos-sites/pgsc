<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Traits\HasGeoTrait;
use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Survos\StateBundle\Traits\MarkingInterface;
use Survos\StateBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['location.obra.read', 'location.read','obra.embedded', 'media.embedded']],
    operations: [new Get(), new GetCollection()]
)]
class Location implements \Stringable, RouteParametersInterface, MarkingInterface
{
    use RouteParametersTrait;
    use MarkingTrait;
    use HasGeoTrait;

    public const array UNIQUE_PARAMETERS = ['locationId' => 'code'];

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        #[Groups(['obra.location.read', 'location.read'])]
        public string $code,
    ) {
        $this->obras = new ArrayCollection();
        $this->marking = 'new';
    }
    public string $id { get => $this->code; }

    #[ORM\Column(length: 255)]
    #[Groups(['location.read', 'obra.location.read'])]
    #[SerializedName('label')]
    public ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['location.read', 'obra.location.read'])]
    public ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['location.read', 'obra.location.read'])]
    public ?string $barrio = null;

    /** @var Collection<int, Obra> */
    #[ORM\OneToMany(targetEntity: Obra::class, mappedBy: 'location')]
    #[Groups(['obra.embedded'])]
    public Collection $obras;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['location.read'])]
    public ?string $type = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['location.read'])]
    public int $obraCount = 0;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $contactName = null;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $phone = null;

    public function __toString(): string
    {
        return $this->name ?? $this->code;
    }

    public function addObra(Obra $obra): void
    {
        if (!$this->obras->contains($obra)) {
            $this->obras->add($obra);
            $obra->location = $this;
            $this->obraCount++;
        }
    }

    public function removeObra(Obra $obra): void
    {
        if ($this->obras->removeElement($obra)) {
            if ($obra->location === $this) {
                $obra->location = null;
            }
            $this->obraCount = max(0, $this->obraCount - 1);
        }
    }
}
