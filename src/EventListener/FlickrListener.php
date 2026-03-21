<?php
declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Obra;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Survos\FlickrBundle\Event\FlickrPhotoEvent;
use Survos\MediaBundle\Service\MediaRegistry;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class FlickrListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly MediaRegistry $mediaRegistry,
    ) {
    }

    #[AsEventListener]
    public function __invoke(FlickrPhotoEvent $event): void
    {
        $description = $event->getPhotoDescription();
        if (!preg_match('/^\$(\S+)/', $description, $matches)) {
            $this->logger->warning('FlickrListener: no $obraCode in description', [
                'description' => $description,
            ]);
            return;
        }

        $obraCode = $matches[1];
        $obra = $this->em->find(Obra::class, $obraCode);
        if (!$obra) {
            $this->logger->warning('FlickrListener: obra not found', ['code' => $obraCode]);
            return;
        }

        $photoData = $event->getPhotoData();
        $url = $photoData['url_o'] ?? null;
        if (!$url) {
            $this->logger->warning('FlickrListener: no url_o in photoData', ['obra' => $obraCode]);
            return;
        }

        $media = $this->mediaRegistry->ensureMedia($url);
        $media->title       = $event->getPhotoTitle();
        $media->description = $description;
        $media->rawData     = array_merge($media->rawData ?? [], [
            'flickr_id'   => $event->getPhotoId(),
            'flickr_type' => isset($photoData['video']) ? 'video' : 'photo',
        ]);

        $obra->addImageCode($media->id);
        $this->em->flush();

        $this->logger->info('FlickrListener: attached photo to obra', [
            'obra'     => $obraCode,
            'mediaId'  => $media->id,
            'flickrId' => $event->getPhotoId(),
        ]);
    }
}
