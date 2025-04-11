<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SacroRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SacroRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['sacro.read']],
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
#[Groups('sacro.read')]
class Sacro implements \Stringable, TranslatableInterface
{
    use TranslatableTrait;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private ?string $id = null
    ) {
        $this->id = $id;
    }


    #[ORM\Column(nullable: true)]
    private ?array $extra = null;

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
        return $this->extra['label'];
    }

    #[Groups('sacro.read')]
    public function getNotes(?string $locale = null): ?string
    {
        return $this->translate($locale)->getNotes();
    }
    #[Groups('sacro.read')]
    public function getLabel(?string $locale = null): ?string
    {
        return $this->translate($locale)->getLabel();
    }

    #[Groups('sacro.read')]
    public function getDescription(?string $locale = null): ?string
    {
        return $this->translate($locale)->getDescription();
    }
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
}
