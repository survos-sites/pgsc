<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\StrRepository;
use Survos\BabelBundle\Entity\Base\StrBase;

#[ORM\Entity(repositoryClass: StrRepository::class)]
#[ORM\Table(name: 'str')]
class Str extends StrBase {}
