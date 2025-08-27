<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\MediaFieldsTrait;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\BabelBundle\Attribute\BabelStorage;
use Survos\BabelBundle\Attribute\Translatable;
use Survos\BabelBundle\Contract\TranslatableResolvedInterface;
use Survos\BabelBundle\Entity\Traits\TranslatableHooksTrait;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['artist.read', 'artist.obra.read', 'media.read']],
    operations: [new Get(), new GetCollection()]
)]
#[Assert\EnableAutoMapping]
#[BabelStorage]
class Artist implements \Stringable, RouteParametersInterface, TranslatableResolvedInterface
{
    use RouteParametersTrait;
    use TranslatableHooksTrait;
    use MediaFieldsTrait;

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_OTHER = 'other';
    const GENDERS = [self::GENDER_FEMALE, self::GENDER_MALE, self::GENDER_OTHER];

    // make this an ENUM?
    public const STUDIO_VISITABLE = [
            'studio.open',
            'studio.appointment',
            'studio.closed',
        ];



    public const array UNIQUE_PARAMETERS = ['artistId' => 'code'];

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        #[Groups(['artist.read'])]
        public string $code,
    ) {
        $this->obras = new ArrayCollection();
        $this->initMediaCollections();
    }

    public string $id { get => $this->code; }

    #[ORM\Column(length: 255)]
    #[Groups(['artist.read','obra.artist.read'])]
    public ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['artist.read'])]
    public ?int $birthYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    #[Groups(['artist.read'])]
    public ?string $email = null;

    #[ORM\Column(length: 24, nullable: true)]
    #[Groups(['artist.read'])]
    public ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['artist.read'])]
    public ?string $instagram = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['translation.orig'])]
    public ?string $bioBacking = null;

    #[Translatable]
    #[Groups(['translatable','artist.read'])]
    public ?string $bio {
        get => $this->resolveTranslatable('bio', $this->bioBacking, 'bio');
        set => $this->bioBacking = $value;
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $sloganBacking = null;

    #[Translatable]
    #[Groups(['translatable','artist.read'])]
    public ?string $slogan {
        get => $this->resolveTranslatable('slogan', $this->sloganBacking, 'slogan');
        set => $this->sloganBacking = $value;
    }

    /** @var Collection<int, Obra> */
    #[ORM\OneToMany(targetEntity: Obra::class, mappedBy: 'artist', orphanRemoval: true)]
    #[Groups(['artist.obra.read'])]
    public Collection $obras;

    #[ORM\Column(nullable: true)]
    public int $obraCount = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $languages = null;

    #[ORM\Column(length: 22, nullable: true)]
    public ?string $gender = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $social = null;

    public function __toString(): string
    {
        return $this->name ?? $this->code;
    }

    public function addObra(Obra $obra): void
    {
        if (!$this->obras->contains($obra)) {
            $this->obras->add($obra);
            $obra->artist = $this;
            $this->obraCount++;
        }
    }

    public function removeObra(Obra $obra): void
    {
        if ($this->obras->removeElement($obra)) {
            if ($obra->artist === $this) {
                $obra->artist = null;
            }
            $this->obraCount = max(0, $this->obraCount - 1);
        }
    }
}
