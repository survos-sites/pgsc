<?php

namespace App\EventListener;

use App\Command\LoadCommand;
use App\Entity\Obra;
use App\Entity\Media; // Adjust to your Media entity
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\FlickrBundle\Event\FlickrPhotoEvent;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Psr\Log\LoggerInterface;

class FlickrListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private MediaRepository $mediaRepository,
    ) {
    }

    #[AsEventListener()]
    public function __invoke(FlickrPhotoEvent $event): void
    {

        if (preg_match('/^\$(.*)\b/', $event->getPhotoDescription(), $matches)) {
            $obraCode = $matches[1];
        } else {
            $this->logger->warning('Obra code not found in description', ['description' => $event->getPhotoDescription()]);
            return;
        }

        $photoData = $event->getPhotoData();

        $this->logger->info('Processing Flickr photo for Obra', [
            'obra_code' => $obraCode,
            'photo_id' => $event->getPhotoId(),
            'photo_title' => $event->getPhotoTitle(),
            'description' => $event->getPhotoDescription(),
        ]);


            if (!$obra = $this->entityManager->find(Obra::class, $obraCode)) {
                $this->logger->warning('Obra not found', ['code' => $obraCode]);
                return;
            }
            $obra = $this->findOrCreateObra($obraCode);

            $this->attachPhotoToObra($obra, $photoData, $event);

            $this->entityManager->flush();

        try {
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
        $url = $photoData['url_o'];
        $imgCode = SaisClientService::calculateCode($url, LoadCommand::SAIS_ROOT);
        $obra->addImageCode($imgCode);
        // $media = $this->upsertMedia($imgCode, original: $url, type: 'image');
        if (!$media = $this->mediaRepository->find($imgCode)) {
            $media = new Media($imgCode);
            $this->entityManager->persist($media);
            $media->originalUrl = $url;
        }

//        dd($media->resized, $photoData);
        // Update media with Flickr data

        $media->title = $event->getPhotoTitle();
        $media->description = $event->getPhotoDescription();
        $media->flickrId = $event->getPhotoId();

        // Set main Flickr URL if available
        $directUrls = $event->getDirectUrls();
        return;
        dd($directUrls, $media);

        if (isset($photoData['urls']['url'][0]['_content'])) {
            $media->setFlickrUrl($photoData['urls']['url'][0]['_content']);
        }

        // Add direct URLs for different sizes if available (info_level = 'full')
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