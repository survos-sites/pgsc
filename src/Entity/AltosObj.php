<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\AltosObjRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AltosObjRepository::class)]
#[ORM\Table(name: 'altos_obj')]
#[ORM\Index(name: 'idx_altosobj_code', fields: ['code'])]
#[ORM\Index(name: 'idx_altosobj_museo', fields: ['museoProcedencia'])]
#[ORM\Index(name: 'idx_altosobj_loc', fields: ['loc'])]
final class AltosObj implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    /** Sheet column: "cons!" */
    #[ORM\Column(nullable: true)]
    public ?int $cons = null;

    /** Sheet column: "code*" (required in sheet) */
    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    public string $code = '' {
        set => $this->code = strtoupper(trim((string)$value));
        get => $this->code;
    }

    /** "Museo de procedencia" */
    #[ORM\Column(length: 120, nullable: true)]
    public ?string $museoProcedencia = null {
        set => $this->museoProcedencia = $value === null ? null : trim((string)$value);
        get => $this->museoProcedencia;
    }

    /** "location" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $location = null {
        set => $this->location = $value === null ? null : trim((string)$value);
        get => $this->location;
    }

    /** "location 2" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $location2 = null {
        set => $this->location2 = $value === null ? null : trim((string)$value);
        get => $this->location2;
    }

    /** "title.es*" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $title_es = null {
        set => $this->title_es = $value === null ? null : trim((string)$value);
        get => $this->title_es;
    }

    /** "title.tzh*" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $title_tzh = null {
        set => $this->title_tzh = $value === null ? null : trim((string)$value);
        get => $this->title_tzh;
    }

    /** "title.tl" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $title_tl = null {
        set => $this->title_tl = $value === null ? null : trim((string)$value);
        get => $this->title_tl;
    }

    /** "description*" */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null {
        set => $this->description = $value === null ? null : rtrim((string)$value);
        get => $this->description;
    }

    /** "description.tzh*" */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description_tzh = null {
        set => $this->description_tzh = $value === null ? null : rtrim((string)$value);
        get => $this->description_tzh;
    }

    /** "tecnica" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $tecnica = null {
        set => $this->tecnica = $value === null ? null : trim((string)$value);
        get => $this->tecnica;
    }

    /** "medidas" */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $medidas = null {
        set => $this->medidas = $value === null ? null : trim((string)$value);
        get => $this->medidas;
    }

    /** "image*" */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $image = null {
        set => $this->image = $value === null ? null : trim((string)$value);
        get => $this->image;
    }

    /** "image.s3*" */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $image_s3 = null {
        set => $this->image_s3 = $value === null ? null : trim((string)$value);
        get => $this->image_s3;
    }

    /** "Fotografía " (header shows trailing space; keep it generic) */
    #[ORM\Column(length: 120, nullable: true)]
    public ?string $fotografia = null {
        set => $this->fotografia = $value === null ? null : trim((string)$value);
        get => $this->fotografia;
    }

    /** "value!" — keep as string to preserve currency formatting */
    #[ORM\Column(length: 64, nullable: true)]
    public ?string $value_display = null {
        set => $this->value_display = $value === null ? null : trim((string)$value);
        get => $this->value_display;
    }

    /**
     * Raw linking token from sheet (e.g., "vit-20"). We’ll resolve this to a Loc via importer.
     * Keeping it denormalized as well helps with debugging / re-imports.
     * Sheet column: "ubi"
     */
    #[ORM\Column(length: 120, nullable: true)]
    public ?string $ubi = null {
        set => $this->ubi = $value === null ? null : strtolower(trim((string)$value));
        get => $this->ubi;
    }

    /**
     * Resolved relation to Loc (resolve by Loc.code in importer).
     */
    #[ORM\ManyToOne(targetEntity: Loc::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    public ?Loc $loc = null;

    public function __toString(): string
    {
        return $this->code ? ($this->code . ' — ' . ($this->title_es ?? $this->title_tzh ?? $this->title_tl ?? $this->description ?? '')) : (string)($this->title_es ?? '');
    }
}
