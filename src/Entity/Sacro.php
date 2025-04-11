<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SacroRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;

#[ORM\Entity(repositoryClass: SacroRepository::class)]
#[ApiResource]
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

    public function getNotes(?string $locale = null): ?string
    {
        return $this->translate($locale)->getNotes();
    }
    public function getDescription(?string $locale = null): ?string
    {
        return $this->translate($locale)->getDescription();
    }
}
