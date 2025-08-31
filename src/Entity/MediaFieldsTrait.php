<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Canonical media trait: persisted codes + virtual relations + helpers.
 */
trait MediaFieldsTrait
{
    // ---- persisted fields ----

    /** Primary media source (e.g. a Google Drive URL for the main image) */
    #[ORM\Column(length: 1024, nullable: true)]
    #[Groups(['media.read','artist.read','obra.read'])]
    public ?string $driveUrl = null;

    /** YouTube URL (not stored as Media) */
    #[ORM\Column(length: 1024, nullable: true)]
    #[Groups(['media.read','artist.read','obra.read'])]
    public ?string $youtubeUrl = null;

    /** Single audio media code (resolved to Media by listener) */
    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['media.read','artist.read','obra.read'])]
    public ?string $audioCode = null;

    #[Groups(['media.read','artist.read','obra.read'])]
    public ?string $youtubeId {
        get => $this->youtubeUrl ? pathinfo($this->youtubeUrl, PATHINFO_BASENAME) : null;
    }

    /** Array of SAIS media codes for images */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media.read','artist.read','obra.read'])]
    public ?array $imageCodes = null;

    // ---- virtual (populated by PopulateImagesListener) ----

    /**
     * @var Collection<int,\App\Entity\Media>
     */
    #[Groups(['media.read','artist.read','obra.read'])]
    public Collection $images;

    protected function initMediaCollections(): void
    {
        $this->images ??= new ArrayCollection();
    }

    // ---- helpers ----

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
        if (!in_array($imageCode, $codes, true)) {
            $codes[] = $imageCode;
            $this->imageCodes = $codes;
        }
        return $this;
    }

    public function removeImageCode(string $imageCode): static
    {
        $codes = $this->getImageCodes();
        $key = array_search($imageCode, $codes, true);
        if ($key !== false) {
            unset($codes[$key]);
            $this->imageCodes = array_values($codes);
        }
        return $this;
    }

    #[Groups(['artist.read','obra.read'])]
    public function getPrimaryImageCode(): ?string
    {
        $codes = $this->getImageCodes();
        return $codes[0] ?? null;
    }

    #[Groups(['artist.read','obra.read'])]
    public function getImageCount(): int
    {
        return count($this->getImageCodes());
    }

    public function clearImageCodes(): static
    {
        $this->imageCodes = [];
        return $this;
    }

    public function setPrimaryImageCode(string $imageCode): static
    {
        $this->imageCodes = [$imageCode];
        return $this;
    }

    #[Groups(['media.read'])]
    public ?string $thumbnailUrl {
        get => $this->images->first() ? $this->images->first()->thumbnailUrl : null;
    }

    #[Groups(['media.read'])]
    public ?Media $image { get => $this->firstByType('image'); }
    #[Groups(['media.read'])]
    // populated in PopulateImagesListener
    public ?Media $video; #  { get => $this->firstByType('video'); }
    public ?Media $audio; #  { get => $this->firstByType('audio'); }
    #[Groups(['media.read'])]
    public ?string $audioUrl {
        get => $this->audio?->resized ? $this->audio->resized['large'] : null;
    }

    private function firstByType(string $type): ?Media
    {
        $image =  $this->images->filter(fn($m) => $m->type === $type)->first();
        return $image ?: null;
    }



}
