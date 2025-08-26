<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Survos\BabelBundle\Attribute\Translatable;
use Survos\BabelBundle\Entity\Traits\TranslatableHooksTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;

trait TranslatableFieldsTrait
{
    use TranslatableHooksTrait;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $descriptionBacking = null;

    #[Translatable]
    #[Groups(['translatable'])]
    public ?string $description {
        get => $this->resolveTranslatable('description', $this->descriptionBacking, 'description');
        set => $this->descriptionBacking = $value;
    }

}