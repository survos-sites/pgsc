<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Survos\BabelBundle\Attribute\Translatable;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;

trait TranslatableFieldsTrait
{

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $descriptionBacking = null;

    #[Translatable]
    #[Groups(['translatable'])]
    public ?string $description {
        get => $this->resolveTranslatable('description', $this->descriptionBacking, 'description');
        set => $this->descriptionBacking = $value;
    }

}