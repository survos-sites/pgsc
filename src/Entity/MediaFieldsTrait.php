<?php
declare(strict_types=1);

namespace App\Entity;

use App\Trait\CollectionNullHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\MediaBundle\Entity\BaseMedia;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Canonical media trait: persisted codes + virtual relations + helpers.
 *
 * imageCodes stores the BaseMedia->id values (xxh3 hash of URL, from MediaIdentity).
 * The $images collection is populated on postLoad by PopulateImagesListener.
 * Use the Twig function media_resize($image, 'small'|'medium'|'large') for URLs.
 */
trait MediaFieldsTrait
{
    use CollectionNullHelperTrait;

    // ---- persisted fields ----

    /** Primary media source URL (e.g. Google Drive URL for the artwork photo) */
    #[ORM\Column(length: 1024, nullable: true)]
    #[Groups(['media.read', 'artist.read', 'obra.read'])]
    public ?string $driveUrl = null;

    /** YouTube URL (not stored as Media) */
    #[ORM\Column(length: 1024, nullable: true)]
    #[Groups(['media.read', 'artist.read', 'obra.read'])]
    public ?string $youtubeUrl = null;

    /** Single audio media id (BaseMedia->id, resolved by listener) */
    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['media.read', 'artist.read', 'obra.read'])]
    public ?string $audioCode = null;

    #[Groups(['artist.read', 'obra.read'])]
    public ?string $youtubeId {
        get => $this->youtubeUrl ? pathinfo($this->youtubeUrl, PATHINFO_BASENAME) : null;
    }

    /** Array of BaseMedia->id values for images */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media.read', 'artist.read', 'obra.read'])]
    public ?array $imageCodes = null;

    // ---- virtual (populated by PopulateImagesListener on postLoad) ----

    /** @var Collection<int, BaseMedia> */
    #[Groups(['media.read', 'artist.read', 'obra.read'])]
    public Collection $images;

    public ?BaseMedia $audio = null;
    public ?BaseMedia $video = null;

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

    #[Groups(['artist.read', 'obra.read'])]
    public function getPrimaryImageCode(): ?string
    {
        return $this->getImageCodes()[0] ?? null;
    }

    #[Groups(['artist.read', 'obra.read'])]
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

    /** First image BaseMedia entity, or null. Use media_resize() in Twig for the URL. */
    #[Groups(['media.read', 'obra.read', 'artist.read'])]
    public ?BaseMedia $image {
        get => $this->firstOrNull($this->images);
    }
}
