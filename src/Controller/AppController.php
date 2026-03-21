<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
use App\Form\ArtistFormType;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Survos\MediaBundle\Repository\MediaRepository;
use App\Service\SyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Survos\GoogleSheetsBundle\Service\GoogleDriveService;

#[Route('/{_locale}')]
final class AppController extends AbstractController
{
    public function __construct(
        private LocationRepository $locationRepository,
        private ArtistRepository $artistRepository,
        private ObraRepository $obraRepository,
        private MediaRepository $mediaRepository,
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor,
        private GoogleDriveService $driveService,
        private \Psr\Log\LoggerInterface $logger,

        #[Autowire('%env(GOOGLE_SPREADSHEET_ID)%')] private ?string $googleSpreadsheetId = null,
    ) {
    }

    #[Route('/extract', name: 'app_download_photos')]
    public function downloadPhotos(): Response
    {
        $cmas = 'https://drive.google.com/file/d/19g88GXmI5DejvnhDQNBQ7lUSZb8Ziq32/view?usp=drivesdk';
        $artist =  'https://drive.google.com/open?id=1BA9tNu-1TpfVD8cx4UfdETobyQ1nzHBi';
        //return a simple text render
        $this->driveService->downloadFileFromUrl(
           $artist,
            'uploads/photos.jpg'
        );

        return new Response('Photos downloaded successfully');
    }

    #[Route('/sync', name: 'app_sync')]
    public function sync(
        SyncService $syncService,
        #[MapQueryParameter] bool $refresh = false,
    ): Response {
        if (empty($this->googleSpreadsheetId)) {
            $this->addFlash('warning', 'GOOGLE_SPREADSHEET_ID is not configured.');
            return $this->redirectToRoute('app_homepage');
        }

        $spreadsheetUrl = 'https://docs.google.com/spreadsheets/d/' . $this->googleSpreadsheetId;

        try {
            $counts = $syncService->sync($refresh);
        } catch (\Throwable $e) {
            return $this->render('app/sync_results.html.twig', [
                'counts'         => ['artists' => 0, 'locations' => 0, 'obras' => 0, 'skipped' => [], 'warnings' => []],
                'spreadsheetUrl' => $spreadsheetUrl,
                'error'          => $e->getMessage(),
            ]);
        }

        return $this->render('app/sync_results.html.twig', [
            'counts'         => $counts,
            'spreadsheetUrl' => $spreadsheetUrl,
            'error'          => null,
        ]);
    }

    #[Route('/home', name: 'app_homepage_with_map')]
    public function home(): Response
    {
        $myMap = (new Map());
        $myMap
            // Explicitly set the center and zoom
//                ->center($point)
            ->zoom(16)
            // Or automatically fit the bounds to the markers
            ->fitBoundsToMarkers()

        ;
        foreach ($this->locationRepository->findAll() as $location) {
            if ($location->getLat()) {
                $point = new Point($location->getLat(), $location->getLng());
                $myMap->addMarker(new Marker(
                    position: $point,
                    title: $location->getName(),
                ));
            }
        }

        return $this->render('app/index.html.twig', [
            'obras' => $this->obraRepository->findAll(),
            'artists' => $this->artistRepository->findAll(),
            'locations' => $this->locationRepository->findAll(),
            'myMap' => $myMap,
        ]);
    }

    #[Route('/artists/{artistId}', name: 'artist_show')]
    #[Template('artist/show.html.twig')]
    public function showArtist(Artist $artist): Response|array
    {
        return [
            'artist' => $artist,
        ];
    }

    #[Route('/landing', name: 'app_homepage')]
    #[Template('landing.html.twig')]
    public function landing(): Response|array
    {
        return [
            'myMap' => $this->getMap(),
            'url' => 'https://vt.survos.com/es/chijal'
        ];
    }

    private function getMap(): Map
    {
        $myMap = (new Map());
        $myMap
            // Explicitly set the center and zoom
//                ->center($point)
            ->zoom(16)
            // Or automatically fit the bounds to the markers
            ->fitBoundsToMarkers()

        ;
        foreach ($this->locationRepository->findAll() as $location) {
            if ($location->lat) {
                $point = new Point($location->lat, $location->lng);
                $myMap->addMarker(new Marker(
                    position: $point,
                    title: $location->name,
                ));
            }
        }
        return $myMap;
    }

    #[Route('/map', name: 'app_map')]
    #[Template('map.html.twig')]
    public function map(): Response|array
    {
        return [
            'myMap' => $this->map(),
            'locations' => $this->locationRepository->findAll(),
        ];
    }


    #[Route('/artist/new', name: 'artist_new')]
    #[Template('artist/new.html.twig')]
    public function newArtist(Request $request): Response|array
    {
        $artist = new Artist();
        $form = $this->createForm(ArtistFormType::class, $artist);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return $this->redirectToRoute('artist_show', $artist->getRp());
        }

        return [
            'artist' => $artist,
            'form' => $form->createView(),
        ];
    }

    #[Route('/artist/edit/{artistId}', name: 'artist_edit')]
    #[Template('artist/new.html.twig')]
    public function editArtist(Request $request, Artist $artist): Response|array
    {
        $form = $this->createForm(ArtistFormType::class, $artist);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('artist_show', $artist->getRp());
        }
        if ('POST' == $request->getMethod()) {
            dd($form, $form->isSubmitted(), $form->isSubmitted() && $form->isValid());
        }

        return [
            'artist' => $artist,
            'form' => $form->createView(),
        ];
    }

    #[Route('/obj/{obraId}', name: 'obj_show')]
    #[Template('obj/show.html.twig')]
    public function showObj(Obra $obra): Response|array
    {
        // Collect image codes from the obra for loading Media entities
        $allMediaCodes = [];
        $imageCodes = $obra->getImageCodes();
        if (!empty($imageCodes)) {
            $allMediaCodes = array_merge($allMediaCodes, $imageCodes);
        }

        // Add audio code if present
        if ($audioCode = $obra->audioCode) {
            $allMediaCodes[] = $audioCode;
        }

        $mediaByCode = [];
        if (!empty($allMediaCodes)) {
            $mediaItems = $this->mediaRepository->createQueryBuilder('m')
                ->where('m.id IN (:codes)')
                ->setParameter('codes', array_unique($allMediaCodes))
                ->getQuery()
                ->getResult();

            foreach ($mediaItems as $media) {
                $mediaByCode[$media->id] = $media;
            }
        }

        return [
            'obj' => $obra,
            'imagesByCode' => $mediaByCode,
            'mediaByCode' => $mediaByCode,
            'audioMedia' => $obra->audioCode ? ($mediaByCode[$obra->audioCode] ?? null) : null,
        ];
    }

    #[Route('/locations/{locationId}', name: 'location_show')]
    #[Template('location/show.html.twig')]
    public function showLocation(Location $location): Response|array
    {
        if ($location->lat) {
            $myMap = (new Map());
            $point = new Point($location->getLat(), $location->getLng());
            $myMap
                // Explicitly set the center and zoom
                ->center($point)
                    ->zoom(16)
                    // Or automatically fit the bounds to the markers
//                    ->fitBoundsToMarkers()
                ->addMarker(new Marker(
                    position: $point,
                    title: $location->getName(),
                ))
            ;
        }

        return [
            'my_map' => $myMap ?? null,
            'location' => $location,
        ];
    }

    #[Route('/location/print/{locationId}', name: 'location_print')]
    #[Template('location/print.html.twig')]
    public function printLocation(Location $location): Response|array
    {
        // Use the existing location->obras relationship
        // Collect all image codes from the obras and load corresponding Media entities
        $allImageCodes = [];
        foreach ($location->obras as $obra) {
            $imageCodes = $obra->getImageCodes();
            if (!empty($imageCodes)) {
                $allImageCodes = array_merge($allImageCodes, $imageCodes);
            }
        }

        $imagesByCode = [];
        if (!empty($allImageCodes)) {
            $images = $this->mediaRepository->createQueryBuilder('m')
                ->where('m.id IN (:codes)')
                ->setParameter('codes', array_unique($allImageCodes))
                ->getQuery()
                ->getResult();

            foreach ($images as $image) {
                $imagesByCode[$image->id] = $image;
            }
        }

        return [
            'location' => $location,
            'obras' => $location->obras,
            'imagesByCode' => $imagesByCode,
        ];
    }

    #[Route('/artist/print/{artistId}', name: 'artist_print')]
    #[Template('artist/print.html.twig')]
    public function printArtist(Artist $artist): Response|array
    {
        // Use the existing artist->obras relationship
        $obras = $artist->obras;

        // Collect all image codes from the obras and artist for batch loading
        $allImageCodes = [];

        // Add artist's image codes
        $artistImageCodes = $artist->getImageCodes();
        if (!empty($artistImageCodes)) {
            $allImageCodes = array_merge($allImageCodes, $artistImageCodes);
        }

        // Add obra image codes
        foreach ($obras as $obra) {
            $imageCodes = $obra->getImageCodes();
            if (!empty($imageCodes)) {
                $allImageCodes = array_merge($allImageCodes, $imageCodes);
            }
        }

        $imagesByCode = [];
        if (!empty($allImageCodes)) {
            $images = $this->mediaRepository->createQueryBuilder('m')
                ->where('m.id IN (:codes)')
                ->setParameter('codes', array_unique($allImageCodes))
                ->getQuery()
                ->getResult();

            foreach ($images as $image) {
                $imagesByCode[$image->id] = $image;
            }
        }

        return [
            'artist' => $artist,
            'obras' => $obras,
            'imagesByCode' => $imagesByCode,
        ];
    }

    #[Route('/admin/print-selected-obras', name: 'print_selected_obras')]
    public function indexLocation(): Response
    {
        dd();

        return $this->render('location/index.html.twig', [
            'locations' => $this->locationRepository->findAll(),
        ]);
    }

    //A Temp route to test  JsonRPC\Client;
    #[Route('/jsonrpc/test', name: 'jsonrpc_test')]
    public function jsonRpcTest(): Response
    {
        $client = 'sais';
        $tools = $this->mcpClientService->listTools($client);

//        //prepare the http client from the jsonrpc pack
//        $httpClient = new \JsonRPC\HttpClient(
//            'https://sais.wip/tools',
//        );
//
//        //add curl proxy option
//        $httpClient->addOption(
//            CURLOPT_PROXY,
//            'http://127.0.0.1:7080'
//        );
//
//        // This is a temporary route to test JsonRPC\Client
//        // You can use this to test the JsonRPC\Client functionality
//        $client = new \JsonRPC\Client('https://sais.wip/tools', false, $httpClient);
//
        $arguments = ['username' => 'rootdd', 'quota' => 1400];

        $result = $this->mcpClientService->callTool($client,
            'create_account',
            $arguments
        );

        dd($result, $tools);

        return new Response('JsonRPC Client created successfully: ' . get_class($client));
    }
}
