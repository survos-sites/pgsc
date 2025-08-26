<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;

trait MediaFieldsTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media.read'])]
    public ?string $audioCode = null; // SAIS code for audio Media entity

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['media.read'])]
    public ?string $driveUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media.read'])]
    public ?string $youtubeUrl = null;


}