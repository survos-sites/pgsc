<?php

namespace App\EventListener;

use App\Entity\Obra;
use App\Entity\Artist;
use App\Repository\ImageRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postLoad)]
final class PopulateImagesListener
{
    public function __construct(private ImageRepository $imageRepository)
    {
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!in_array($entity::class, [Obra::class, Artist::class])) {
            return;
        }

        $codes = $entity->getImageCodes();
        if (!$codes) {
            $entity->setImages([]);
            return;
        }

        // One query to fetch all images by their codes
        $images = $this->imageRepository->findBy(['code' => $codes]);

        // Re-key by image code
        $imagesByCode = [];
        foreach ($images as $image) {
            $imagesByCode[$image->getCode()] = $image;
        }

        // Populate entity
        $entity->setImages($imagesByCode);
    }
}
