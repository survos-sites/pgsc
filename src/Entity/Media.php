<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use App\Workflow\IMediaWorkflow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\WorkflowBundle\Traits\MarkingInterface;
use Survos\WorkflowBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media implements \Stringable, MarkingInterface
{
    use MarkingTrait;
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['media.read'])]
    private(set) ?string $code = null; // SAIS media code as primary key

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media.read','obra.read','artist.read'])]
    public ?array $resized = null; // Array of resized images: {small: "url", medium: "url", large: "url"}

    // candidate for Enum!
    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups(['media.read'])]
    public string $type='image'; // or 'audio'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['media.read'])]
    public ?string $originalUrl = null; // Original Google Drive URL

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media.read'])]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media.read'])]
    private ?int $size = null; // Original file size in bytes

    #[ORM\Column(nullable: true)]
    #[Groups(['media.read'])]
    private ?int $originalWidth = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media.read'])]
    private ?int $originalHeight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media.read'])]
    private ?int $statusCode = null; // HTTP status from SAIS processing

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media.read'])]
    private ?string $blur = null; // Blur hash for placeholder

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media.read'])]
    private ?array $context = null; // Additional metadata from SAIS

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['media.read'])]
    private ?array $exif = null; // EXIF data

    #[ORM\Column]
    #[Groups(['media.read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media.read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        ?string $code = null
    )
    {
        $this->code = $code;
        $this->createdAt = new \DateTimeImmutable();
        $this->marking = IMediaWorkflow::PLACE_NEW;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getResized(): ?array
    {
        return $this->resized ?? [];
    }

    public function setResized(?array $resized): static
    {
        $this->resized = $resized;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getOriginalWidth(): ?int
    {
        return $this->originalWidth;
    }

    public function setOriginalWidth(?int $originalWidth): static
    {
        $this->originalWidth = $originalWidth;
        return $this;
    }

    public function getOriginalHeight(): ?int
    {
        return $this->originalHeight;
    }

    public function setOriginalHeight(?int $originalHeight): static
    {
        $this->originalHeight = $originalHeight;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getBlur(): ?string
    {
        return $this->blur;
    }

    public function setBlur(?string $blur): static
    {
        $this->blur = $blur;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): static
    {
        $this->context = $context;
        return $this;
    }

    public function getExif(): ?array
    {
        return $this->exif;
    }

    public function setExif(?array $exif): static
    {
        $this->exif = $exif;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get the thumbnail URL (small size)
     */
    #[Groups(['media.read'])]
    public function getThumbnailUrl(): ?string
    {
        return $this->resized['small'] ?? null;
    }

    /**
     * Get medium sized image URL
     */
    #[Groups(['media.read'])]
    public function getMediumUrl(): ?string
    {
        return $this->resized['medium'] ?? null;
    }

    /**
     * Get large sized image URL
     */
    #[Groups(['media.read'])]
    public function getLargeUrl(): ?string
    {
        return $this->resized['large'] ?? null;
    }

    /**
     * Check if image has been processed by SAIS
     */
    #[Groups(['media.read'])]
    public function isProcessed(): bool
    {
        return !empty($this->resized) && $this->statusCode === 200;
    }

    /**
     * Get Google Drive file ID from original URL
     */
    public function getDriveId(): ?string
    {
        if (!$this->originalUrl) {
            return null;
        }

        // Extract ID from various Google Drive URL formats
        if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $this->originalUrl, $matches)) {
            return $matches[1];
        }

        if (preg_match('/id=([a-zA-Z0-9-_]+)/', $this->originalUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function __toString(): string
    {
        return $this->code ?? 'Media';
    }
}
