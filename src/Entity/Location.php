<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\LocationType;
use App\Repository\LocationRepository;
use App\Workflow\ILocationWorkflow;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Survos\WorkflowBundle\Traits\MarkingInterface;
use Survos\WorkflowBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['location.obra.read', 'location.read']],
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Location implements \Stringable, RouteParametersInterface, MarkingInterface
{
    use RouteParametersTrait;
    use MarkingTrait;
    public const array UNIQUE_PARAMETERS = ['locationId' => 'id'];

    #[ORM\Column(length: 255)]
    #[Groups(['location.read', 'obra.location.read'])]
    #[SerializedName('label')]
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
    #[Groups(['location.read', 'obra.location.read'])]
    private ?string $code = null;

    #[ORM\Column(type: 'string', length: 24, nullable: true, enumType: LocationType::class)]
    #[Groups(['location.read'])]
    private ?LocationType $type = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['location.read'])]
    private int $obraCount = 0;

    #[ORM\Column(nullable: true)]
    #[Groups(['location.read'])]
    private ?float $lat = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['location.read'])]
    private ?float $lng = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        #[Groups(['obra.location.read', 'location.read'])]
        private(set) ?string $id = null
    )
    {
        $this->obras = new ArrayCollection();
        $this->marking = ILocationWorkflow::PLACE_NEW;
    }

    public function getId(): ?string
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
            ++$this->obraCount;
            $this->obras->add($obra);
            $obra->setLocation($this);
        }

        return $this;
    }

    public function removeObra(Obra $obra): static
    {
        if ($this->obras->removeElement($obra)) {
            --$this->obraCount;
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

    #[Groups(['location.read'])]
    public function getType(): ?LocationType
    {
        return $this->type;
    }

    #[Groups(['location.read'])]
    public function getTypeString(): ?string
    {
        return $this->type?->name;
    }

    public function setType(?LocationType $type): static
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

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): static
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(?float $lng): static
    {
        $this->lng = $lng;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
