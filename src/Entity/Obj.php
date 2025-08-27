<?php

namespace App\Entity;

use App\Repository\ObjRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\MediaFieldsTrait;

#[ORM\Entity(repositoryClass: ObjRepository::class)]
class Obj
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private(set) ?string $id = null
    ) {
    }
    public function getId(): ?string
    {
        return $this->id;
    }
}
