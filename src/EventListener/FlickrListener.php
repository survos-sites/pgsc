<?php

namespace App\EventListener;

use App\Entity\Obra;
use App\Entity\Media; // Adjust to your Media entity
use Doctrine\ORM\EntityManagerInterface;
use Survos\FlickrBundle\Event\FlickrPhotoEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Psr\Log\LoggerInterface;

class FlickrListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[AsEventListener()]
    public function __invoke(FlickrPhotoEvent $event): void
    {
        // Only process photos that have obra codes
        dd($event, $event->getPhotoDescription(), $event->processingContext);
        if (!$event->hasObraCode()) {
            return;
        }

        $obraCode = $event->getObraCode();
        $photoData = $event->getPhotoData();

        $this->logger->info('Processing Flickr photo for Obra', [
            'obra_code' => $obraCode,
            'photo_id' => $event->getPhotoId(),
            'photo_title' => $event->getPhotoTitle()
        ]);

        try {
            $obra = $this->findOrCreateObra($obraCode);

            if (!$obra) {
                $this->logger->warning('Obra not found', ['code' => $obraCode]);
                return;
            }

            $this->attachPhotoToObra($obra, $photoData, $event);

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Error processing Flickr photo for Obra', [
                'obra_code' => $obraCode,
                'photo_id' => $event->getPhotoId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function findOrCreateObra(string $obraCode): ?Obra
    {
        return $this->entityManager
            ->getRepository(Obra::class)
            ->findOneBy(['code' => $obraCode]);
    }

    private function attachPhotoToObra(Obra $obra, array $photoData, FlickrPhotoEvent $event): void
    {
        // Get the first media or create new one
        $media = $obra->getMedias()->first();

        if (!$media) {
            $media = new Media();
            $media->setObra($obra);
            $media->setType('flickr_photo');
            $obra->addMedia($media);
            $this->entityManager->persist($media);
        }

        // Update media with Flickr data
        $media->setTitle($event->getPhotoTitle());
        $media->setDescription($event->getPhotoDescription());
        $media->setFlickrId($event->getPhotoId());

        // Set main Flickr URL if available
        if (isset($photoData['urls']['url'][0]['_content'])) {
            $media->setFlickrUrl($photoData['urls']['url'][0]['_content']);
        }

        // Add direct URLs for different sizes if available (info_level = 'full')
        $directUrls = $event->getDirectUrls();
        if (!empty($directUrls)) {
            $media->setResized($directUrls);
        }

        // Add other metadata
        if (isset($photoData['date_taken'])) {
            $media->setDateTaken(new \DateTime($photoData['date_taken']));
        }

        if (isset($photoData['tags'])) {
            $media->setTags(explode(' ', $photoData['tags']));
        }

        $this->logger->info('Attached Flickr photo to Obra', [
            'obra_code' => $obra->getCode(),
            'photo_id' => $event->getPhotoId(),
            'has_direct_urls' => !empty($directUrls)
        ]);
    }
}