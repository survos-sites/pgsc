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
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;
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
class Artist implements \Stringable, RouteParametersInterface, TranslatableInterface
{
    use RouteParametersTrait;
    use TranslatableTrait;

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_OTHER = 'other';
    const GENDERS = [self::GENDER_FEMALE, self::GENDER_MALE, self::GENDER_OTHER];

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

//    #[ORM\Column(type: JsonTranslationType::TYPE, nullable: true)]
//    #[Groups(['artist.read'])]
//    private ?JsonTranslation $bio = null;

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

    #[ORM\Column(nullable: true)]
    private ?array $languages = null;

    #[ORM\Column(length: 22, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $social = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $Timestamp = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $pronouns = null;

    #[ORM\Column(length: 24, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactMethod = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $studio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $headshot = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $types = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slogan = null;

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

    #[Groups(['artist.read'])]
    public function getBio(): ?string
    {
        return $this->translate()->getBio();
    }

    public function setBio(?string $bio): static
    {
        $this->translate()->setBio($bio);

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
            $obra->setArtist($this);
        }

        return $this;
    }

    public function removeObra(Obra $obra): static
    {
        if ($this->obras->removeElement($obra)) {
            --$this->obraCount;
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

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(array|string|null $languages): static
    {
        $this->languages = $languages ? (is_string($languages) ? explode(',', $languages) : $languages) : $languages;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getSocial(): ?string
    {
        return $this->social;
    }

    public function setSocial(?string $social): static
    {
        $this->social = $social;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->Timestamp;
    }

    public function setTimestamp(DateTimeInterface|string|null $Timestamp): static
    {
        $this->Timestamp = is_string($Timestamp) ? new \DateTime($Timestamp) : $Timestamp;

        return $this;
    }

    public function getPronouns(): ?string
    {
        return $this->pronouns;
    }

    public function setPronouns(?string $pronouns): static
    {
        $this->pronouns = $pronouns;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getContactMethod(): ?string
    {
        return $this->contactMethod;
    }

    public function setContactMethod(?string $contactMethod): static
    {
        $this->contactMethod = $contactMethod;

        return $this;
    }

    public function getStudio(): ?string
    {
        return $this->studio;
    }

    public function setStudio(?string $studio): static
    {
        $this->studio = $studio;

        return $this;
    }

    public function getHeadshot(): ?string
    {
        return $this->headshot;
    }

    public function setHeadshot(?string $headshot): static
    {
        $this->headshot = $headshot;

        return $this;
    }

    public function getTypes(): ?string
    {
        return $this->types;
    }

    public function setTypes(?string $types): static
    {
        $this->types = $types;

        return $this;
    }

    public function getSlogan(): ?string
    {
        return $this->slogan;
    }

    public function setSlogan(?string $slogan): static
    {
        $this->slogan = $slogan;

        return $this;
    }
}
