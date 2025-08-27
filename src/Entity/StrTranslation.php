<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\StrTranslationRepository;
use Survos\BabelBundle\Entity\Base\StrTranslationBase;

#[ORM\Entity(repositoryClass: StrTranslationRepository::class)]
#[ORM\Table(name: 'str_translation')]
class StrTranslation extends StrTranslationBase
{
}
