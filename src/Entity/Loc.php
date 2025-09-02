<?php

namespace App\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Traits\NestedSetEntity;
use App\Repository\LocRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LocRepository::class)]
//#[Gedmo\Tree(type: 'nested')]

class Loc
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Assert\Valid]
    #[Groups(['write'])]
//    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(referencedColumnName: 'id', onDelete: 'CASCADE')]
    public $parent;

    #[Groups(['tree', 'read'])]
    public function getParentId(): ?Uuid
    {
        return $this->parent?->getId();
    }

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy([
        'left' => 'ASC', // @gedmo traits missing?
    ])]
    public $children;


}
