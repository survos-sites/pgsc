<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Trait for entities that can have associated Image entities via SAIS codes
 */
trait ImageCodesTrait
{
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['artist.read', 'obra.read'])]
    private ?array $imageCodes = null; // Array of Image entity codes (SAIS codes)

    public function getImageCodes(): array
    {
        return $this->imageCodes ?? [];
    }

    public function setImageCodes(?array $imageCodes): static
    {
        $this->imageCodes = $imageCodes;
        return $this;
    }

    public function addImageCode(string $imageCode): static
    {
        $codes = $this->getImageCodes();
        if (!in_array($imageCode, $codes)) {
            $codes[] = $imageCode;
            $this->setImageCodes($codes);
        }
        return $this;
    }

    public function removeImageCode(string $imageCode): static
    {
        $codes = $this->getImageCodes();
        $key = array_search($imageCode, $codes);
        if ($key !== false) {
            unset($codes[$key]);
            $this->setImageCodes(array_values($codes));
        }
        return $this;
    }

    /**
     * Get the primary image code (first in array)
     * Used for main image display
     */
    #[Groups(['artist.read', 'obra.read'])]
    public function getPrimaryImageCode(): ?string
    {
        $codes = $this->getImageCodes();
        return $codes[0] ?? null;
    }

    /**
     * Check if entity has any image codes
     */
    #[Groups(['artist.read', 'obra.read'])]
    public function hasImages(): bool
    {
        return !empty($this->getImageCodes());
    }

    /**
     * Get count of associated images
     */
    #[Groups(['artist.read', 'obra.read'])]
    public function getImageCount(): int
    {
        return count($this->getImageCodes());
    }

    /**
     * Clear all image codes
     */
    public function clearImageCodes(): static
    {
        $this->imageCodes = [];
        return $this;
    }

    /**
     * Set a single primary image code (replaces all existing codes)
     * Useful for artists who typically have only one image
     */
    public function setPrimaryImageCode(string $imageCode): static
    {
        $this->setImageCodes([$imageCode]);
        return $this;
    }
}
