<?php

namespace App\Command;

use App\Entity\Obra;
use Doctrine\ORM\EntityManagerInterface;
use Survos\FlickrBundle\Services\FlickrService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Symfony\Component\String\u;

#[AsCommand('app:flickr', 'Import the flickr photos into artists/obras')]
class FlickrCommand
{
    public function __construct(
        private FlickrService $flickrService,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Flickr album ID or URL')]
        string $albumUrl = 'https://www.flickr.com/photos/202304062@N02/albums/72177720328661598/',
        #[Option('Refetch the cached page')]
        bool $refresh = false,
        #[Option('Photos per page')]
        int $perPage = 100,
        #[Option('Show what would be imported without saving')]
        bool $dryRun = false
    ): int
    {
        $albumId = $this->extractAlbumId($albumUrl);
        $userId = $this->extractUserIdFromUrl($albumUrl);

        if (!$albumId) {
            $io->error('Invalid album ID or URL provided');
            return Command::FAILURE;
        }

        if (!$userId) {
            $io->error('Could not extract user ID from URL');
            return Command::FAILURE;
        }

        $io->title('Fetching Flickr Album: ' . $albumId);
        $io->writeln("User ID: {$userId}");

        try {
            // Get album info - requires both albumId and userId
            $albumInfo = $this->flickrService->photosets()->getInfo($albumId, $userId);
            $io->section('Album Information');
            $io->table(['Property', 'Value'], [
                ['Title', $albumInfo['title']],
                ['Description', $albumInfo['description']],
                ['Total Photos', $albumInfo['photos']],
                ['Owner', $albumInfo['owner']]
            ]);

            // Fetch and process photos with pagination using callback
            $processedCount = 0;
            $obraMatches = 0;

            $photoCallback = function($photo, $pageNum, $photoNum, $totalPhotos) use ($io, $userId, $dryRun, &$processedCount, &$obraMatches) {
                $processedCount++;

                // Get detailed photo info including description - requires both photoId and userId
                $photoInfo = $this->flickrService->photos()->getInfo($photo['id'], $userId);

                $io->writeln(sprintf(
                    'Processing photo %d/%d (Page %d): %s',
                    $processedCount,
                    $totalPhotos,
                    $pageNum,
                    $photoInfo['title']
                ));

                // Check if description starts with obra code
                $description = $photoInfo['description'] ?? '';
                $obraCode = $this->extractObraCode($description);

                if ($obraCode) {
                    $obraMatches++;
                    $io->writeln("  → Found Obra code: {$obraCode}", SymfonyStyle::VERBOSITY_VERBOSE);

                    if (!$dryRun) {
                        $this->attachToObra($obraCode, $photoInfo, $io);
                    } else {
                        $io->writeln("  → [DRY RUN] Would attach to Obra: {$obraCode}");
                    }
                } else {
                    $io->writeln("  → No Obra code found", SymfonyStyle::VERBOSITY_VERBOSE);
                }

                // Show photo details in verbose mode
                if ($io->isVerbose()) {
                    $io->writeln("  → Photo ID: {$photo['id']}");
                    $io->writeln("  → Title: {$photoInfo['title']}");
                    $io->writeln("  → Description: " . substr($description, 0, 100) . (strlen($description) > 100 ? '...' : ''));
                    $io->writeln("  → URL: {$photoInfo['urls']['url'][0]['_content']}");
                }

                return true; // Continue processing
            };

            $this->fetchAllPhotos($albumId, $userId, $perPage, $io, $photoCallback);

            $io->success(sprintf(
                'Successfully processed %d photos from album "%s". Found %d photos with Obra codes.',
                $processedCount,
                $albumInfo['title'],
                $obraMatches
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error fetching album: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function extractAlbumId(string $input): ?string
    {
        // If it's already just an ID
        if (preg_match('/^\d+$/', $input)) {
            return $input;
        }

        // Extract from Flickr URL
        if (preg_match('/albums\/(\d+)/', $input, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractUserIdFromUrl(string $input): ?string
    {
        // Extract user ID from URL like: https://www.flickr.com/photos/202304062@N02/albums/...
        if (preg_match('/\/photos\/([^\/]+)\//', $input, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function fetchAllPhotos(string $albumId, string $userId, int $perPage, SymfonyStyle $io, callable $photoCallback = null): array
    {
        $allPhotos = [];
        $page = 1;
        $totalPages = 1;
        $totalPhotos = 0;

        $io->section('Fetching and Processing Photos');
        $io->progressStart();

        do {
            $params = [
                'page' => $page,
                'per_page' => $perPage,
                'extras' => 'description,url_m,url_l,url_o,tags,machine_tags'
            ];

            // Call getPhotos with albumId, userId, and params
            $response = $this->flickrService->photosets()->getPhotos($albumId, $userId, $params);

            $totalPages = $response['pages'];
            $totalPhotos = $response['total'];

            // Process each photo immediately if callback provided
            if ($photoCallback) {
                foreach ($response['photo'] as $index => $photo) {
                    $photoNumber = (($page - 1) * $perPage) + $index + 1;
                    $continue = $photoCallback($photo, $page, $photoNumber, $totalPhotos);

                    if (!$continue) {
                        $io->writeln('Processing stopped by callback');
                        return $allPhotos;
                    }
                }
            } else {
                $allPhotos = array_merge($allPhotos, $response['photo']);
            }

            $io->progressAdvance(count($response['photo']));

            $io->writeln(sprintf(
                " Page %d/%d complete (%d photos processed)",
                $page,
                $totalPages,
                count($response['photo'])
            ), SymfonyStyle::VERBOSITY_VERBOSE);

            $page++;
        } while ($page <= $totalPages);

        $io->progressFinish();

        if (!$photoCallback) {
            $io->writeln(sprintf("Total photos fetched: %d", count($allPhotos)));
        } else {
            $io->writeln(sprintf("Total photos processed: %d", $totalPhotos));
        }

        return $allPhotos;
    }

    private function extractObraCode(string $description): ?string
    {
        // Look for obra codes at the beginning of the description
        // Assuming format like "OBR001:" or "OBRA-123:" etc.
        if (u($description)->startsWith('$')) {
            return $description;
        }
        if (preg_match('/\$(.*)\w/', $description, $matches)) {
            return $matches[1];
            dd($description, $matches);
        }
        if (preg_match('/^(OBR[A]?[-_]?\d+):?\s/i', $description, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function attachToObra(string $obraCode, array $photoInfo, SymfonyStyle $io): void
    {
        try {
            // Find the Obra by code
            $obra = $this->entityManager
                ->getRepository(Obra::class)
                ->findOneBy(['code' => $obraCode]);

            if (!$obra) {
                $io->warning("  → Obra with code '{$obraCode}' not found");
                return;
            }

            // Get the first media or create new one
            $media = $obra->getMedias()->first() ?: new Media();

            // Extract farm, server, id, secret from photo info for direct URLs
            $farm = $photoInfo['farm'];
            $server = $photoInfo['server'];
            $photoId = $photoInfo['id'];
            $secret = $photoInfo['secret'];

            // Build direct Flickr farm URLs for different sizes
            $resizedUrls = [
                'thumbnail' => "https://farm{$farm}.staticflickr.com/{$server}/{$photoId}_{$secret}_t.jpg", // 100px
                'small' => "https://farm{$farm}.staticflickr.com/{$server}/{$photoId}_{$secret}_m.jpg",     // 240px
                'medium' => "https://farm{$farm}.staticflickr.com/{$server}/{$photoId}_{$secret}_z.jpg",    // 640px
                'large' => "https://farm{$farm}.staticflickr.com/{$server}/{$photoId}_{$secret}_b.jpg",     // 1024px
                'original' => "https://farm{$farm}.staticflickr.com/{$server}/{$photoId}_{$secret}_o.jpg"   // original
            ];
            dd($resizedUrls);

            // Create media entry (adjust based on your Media entity structure)
            /*
            if (!$media->getId()) {
                $media->setObra($obra);
                $media->setTitle($photoInfo['title']);
                $media->setDescription($photoInfo['description']);
                $media->setType('flickr_photo');
                $obra->addMedia($media);
            }

            $media->setFlickrId($photoInfo['id']);
            $media->setFlickrUrl($photoInfo['urls']['url'][0]['_content'] ?? '');
            $media->setResized($resizedUrls);

            $this->entityManager->persist($media);
            $this->entityManager->persist($obra);
            */

            // For now, just log what we would do
            $io->writeln("  → Would attach photo '{$photoInfo['title']}' to Obra '{$obraCode}'");
            $io->writeln("  → Resized URLs:", SymfonyStyle::VERBOSITY_VERBOSE);
            foreach ($resizedUrls as $size => $url) {
                $io->writeln("    → {$size}: {$url}", SymfonyStyle::VERBOSITY_VERBOSE);
            }

        } catch (\Exception $e) {
            $io->error("  → Error attaching photo to Obra '{$obraCode}': " . $e->getMessage());
        }
    }
}