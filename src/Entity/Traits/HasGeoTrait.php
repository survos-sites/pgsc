<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait HasGeoTrait
{
    #[Groups(['location.read'])]
    #[ORM\Column(nullable: true)]
    public ?float $lat = null;

    #[Groups(['location.read'])]
    #[ORM\Column(nullable: true)]
    public ?float $lng = null;

    public function setGeoFromString(?string $geo): void
    {
        if (!$geo) { return; }
        $parts = array_map('trim', explode(',', $geo));
        if (\count($parts) === 2) {
            $this->lat = is_numeric($parts[0]) ? (float)$parts[0] : null;
            $this->lng = is_numeric($parts[1]) ? (float)$parts[1] : null;
        }
    }
}
