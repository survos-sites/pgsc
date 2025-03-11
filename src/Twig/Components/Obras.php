<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use App\Entity\Obra;

#[AsTwigComponent]
final class Obras
{
    public bool $x=true;
    public bool $showLocation=true;
    public bool $showArtist=true;

    /** @var array<Obra> */
    private array $obras = [];
}
