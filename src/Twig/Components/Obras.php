<?php

namespace App\Twig\Components;

use App\Entity\Obra;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Obras
{
    public bool $x = true;
    public bool $showLocation = true;
    public bool $showArtist = true;

    /** @var array<Obra> */
    private array $obras = [];
}
