<?php

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Trait for entities that can have associated Media entities via SAIS codes
 *
 * Loaded in postload, not regular fields.
 */
trait ImageCodesTrait
{
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media.read'])]
    public ?array $imageCodes = null; // Array of Media entity codes (SAIS codes) of images

    #[Groups(['media.read'])]
    public ?array $audioCodes = null; // points to media

    #[Groups(['media.read','obra.read','artist.read'])]
    public Collection $images;


    public function getImageCodes(): array
    {
        return $this->imageCodes ?? [];
    }
    public function getImages(): Collection
    {
        return $this->images ?? new ArrayCollection();
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
//    #[Groups(['artist.read', 'obra.read'])]
//    public function hasImages(): bool
//    {
//        return count($this->imageCodes) > 0;
//    }

    /**
     * Get count of associated images
     */
    #[Groups(['artist.read', 'obra.read'])]
    public function getImageCount(): int
    {
        return count($this->imageCodes??[]);
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

    #[Groups(['media.read'])]
    public function getThumbnailUrl(): ?string {
        return ($first = $this->getImages()->first()) ? $first->getThumbnailUrl() : null;
    }


}
