<?php
declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Artist;
use App\Entity\Obra;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use Survos\MediaBundle\Repository\MediaRepository;

#[AsDoctrineListener(event: Events::postLoad)]
final class PopulateImagesListener
{
    public function __construct(private readonly MediaRepository $mediaRepository)
    {
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Obra && !$entity instanceof Artist) {
            return;
        }

        $codes = $entity->getImageCodes();
        $entity->images = $codes
            ? new ArrayCollection($this->mediaRepository->findBy(['id' => $codes]))
            : new ArrayCollection();

        if ($entity instanceof Obra && $audioCode = $entity->audioCode) {
            $entity->audio = $this->mediaRepository->find($audioCode);
        }
    }
}
