<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\LocRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Survos\Tree\TreeInterface;
use Survos\Tree\Traits\TreeTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\AltosObj;

#[ORM\Entity(repositoryClass: LocRepository::class)]
#[ORM\Table(name: 'loc')]
#[ORM\UniqueConstraint(name: 'uniq_loc_code', fields: ['code'])]
#[ORM\Index(name: 'idx_loc_type', fields: ['type'])]
//#[Gedmo\Tree(type: 'nested')]
final class Loc implements \Stringable
{
//    use TreeTrait;

    public function __construct()
    {
        // if you don't already have a constructor, add this
        $this->altosObjects = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    /** @var Collection<int, AltosObj> */
    #[ORM\OneToMany(mappedBy: 'loc', targetEntity: AltosObj::class, cascade: ['persist'], orphanRemoval: false)]
    public Collection $altosObjects;

    public function addAltosObj(AltosObj $obj): self
    {
        if (!$this->altosObjects->contains($obj)) {
            $this->altosObjects->add($obj);
            $obj->loc = $this; // keep owning side in sync
        }
        return $this;
    }

    public function removeAltosObj(AltosObj $obj): self
    {
        if ($this->altosObjects->removeElement($obj)) {
            if ($obj->loc === $this) {
                $obj->loc = null;
            }
        }
        return $this;
    }

    /**
     * Optional natural key (e.g., SALA-1, VIT-1-2, POS-1-2-3).
     * Unique so re-imports can upsert reliably.
     */
    #[ORM\Column(length: 80, unique: true, nullable: true)]
    public ?string $code = null {
        set => $this->code = $value === null ? null : strtoupper(trim($value));
        get => $this->code;
    }

    /**
     * Semantic type for this node (e.g., sala, vitrina, ficha_descriptiva, location).
     */
    #[ORM\Column(length: 40)]
    #[Assert\NotBlank]
    public string $type = 'location' {
        set => $this->type = strtolower(trim($value));
        get => $this->type;
    }

    /**
     * Visible label/title of the location.
     * Example input: "SALA-1: Intro" → importer should set code separately;
     * hook keeps label clean.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    public string $label {
        set => $this->label = trim(preg_replace('/^(SALA-\d+|VIT-\d+-\d+|POS-\d+-\d+-\d+):\s*/i', '', (string)$value) ?? '');
        get => $this->label;
    }

    /**
     * Optional short line under the label.
     */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $subtitle = null {
        set => $this->subtitle = ($value === null) ? null : trim((string)$value);
        get => $this->subtitle;
    }

    /**
     * Long Markdown body for this node (printed at any depth).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null {
        set => $this->description = ($value === null) ? null : rtrim((string)$value) . (str_ends_with((string)$value, "\n") ? '' : '');
        get => $this->description;
    }

    public function __toString(): string
    {
        return $this->code ? "{$this->label} [{$this->code}]" : $this->label;
    }
}
