<?php

namespace App\EventListener;

use App\Entity\Obra;
use App\Entity\Artist;
use App\Repository\MediaRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postLoad)]
final class PopulateImagesListener
{
    public function __construct(private MediaRepository $imageRepository)
    {
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!in_array($entity::class, [Obra::class, Artist::class])) {
            return;
        }

        // Load images
        $codes = $entity->getImageCodes();
        if (!$codes) {
            $images = new ArrayCollection();
        } else {
            $images = new ArrayCollection($this->imageRepository->findBy(['code' => $codes]));
        }

        // One query to fetch all images by their codes
//        assert(!is_array($images), join(", ", $codes));
        $entity->images = $images;

        // Re-key by image code
        $imagesByCode = [];
        foreach ($images as $image) {
            $imagesByCode[$image->getCode()] = $image;
        }

        // Load audio for Obra entities
        if ($entity instanceof Obra && $audioCode = $entity->audioCode) {
            $audioMedia = $this->imageRepository->findByCode($audioCode);
            $entity->audio = $audioMedia;
        }

        // Populate entity
    }
}
