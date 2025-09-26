<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Media;
use App\Entity\Location;
use App\Entity\Obra;
use App\Form\ArtistFormType;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\MediaRepository;
use App\Repository\ObraRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\McpBundle\Service\McpClientService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use function Symfony\Component\String\u;
use Survos\GoogleSheetsBundle\Service\SheetService;
use Survos\GoogleSheetsBundle\Service\GoogleDriveService;

//call symfony String
use Symfony\Component\String\Slugger\AsciiSlugger;

use Survos\SaisBundle\Mcp\Schema\CreateUserSchema;
use Survos\SaisBundle\Model\AccountSetup;

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
        private McpClientService $mcpClientService,

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
        SheetService $sheetService,
        #[MapQueryParameter] bool $refresh = false,
    ): Response {

        // Check if Google Spreadsheet ID is configured
        if (empty($this->googleSpreadsheetId)) {
            $this->logger->warning('Google Spreadsheet ID not configured, skipping sync');
            return $this->render('app/index.html.twig', [
                'message' => 'Google Spreadsheet ID not configured in this environment. Sync functionality disabled.'
            ]);
        }

        try {
            $this->logger->info('Starting sync process', ['spreadsheetId' => $this->googleSpreadsheetId]);
            $spreadsheet = $sheetService->getGoogleSpreadSheet($this->googleSpreadsheetId);
//        $x = $sheetService->downloadSheetToLocal('@artists', 'data/ardata.csv');
//        dd($x);

//        $accessor = new PropertyAccessor();
        $data = $sheetService->getData(
            $this->googleSpreadsheetId,
            $refresh,
            function ($sheet, $csv) {
                //save to local file filename : data/{spreadsheet_id}_{sheet}.csv
                if (empty($sheet)) {
                    $this->logger->warning('Empty sheet name encountered, skipping');
                    return;
                }
                $filePath = sprintf('%s/data/%s.csv', $this->getParameter('kernel.project_dir'), u($sheet)->snake()->toString());
                if (!is_dir(dirname($filePath))) {
                    mkdir(dirname($filePath), 0777, true);
                }
                file_put_contents($filePath, $csv);
                return;
                //dd(sprintf('Saved %s to %s', $sheet, $filePath));
                $entityClass = match ($sheet) {
                    'DATOS ARTISTAS' => Artist::class,
                    'DATOS LOCALES' => Location::class,
                    default => null,
                };
                if (!$entityClass) {
                    return;
                }
                $key = match ($entityClass) {
                    Artist::class => 'email',
                    Location::class => 'code' // ??
                };

                $reader = Reader::createFromString($csv);
                $reader->setHeaderOffset(0);
                try {
                    foreach ($reader as $row) {
                        //convert all row keys to snake case and trim them but keep the original array values as is
                        $row = array_combine(
                            array_map(fn($key) => u($key)->snake()->toString(), array_keys($row)),
                            array_values($row)
                        );

                        //let s do a full mapping patch since the column names comes as :
                        //timestamp, nombre_completo_yo_artístico, año_de_nacimiento, pronombres_preferidos, redes_sociales_de_ser_posible_pon_el_enlace_sino_nombre_y_la_red_social_a_la_que_corresponde_dicho_nombre, teléfono, método_de_contacto_preferido, short_bio, biografía_larga23_párrafos, tu_estudio_esta_abierto_al_público, si_tu_estudio_esta_abierto_al_público_por_favor_incluye_la_dirección_yo_un_enlace_ó_las_instrucciones_de_visita, te_pedimos_una_foto_de_los_hombros_para_arriba_esta_foto_sera_expuesta_junto_con_tu_biografía_en_la_app_del_proyecto, aparte_de_español_qué_otras_lenguas_hablas, incluye_todos_los_tipos_de_arte_que_realizas, un_slogan_ó_tagline_sobre_quién_eres_ej_artista_chiapaneco_arte_textil35_palabras_max, email

                        $columnsMapping = [
                            'timestamp' => 'timestamp',
                            'nombre_completo_yo_artístico' => 'name',
                            'año_de_nacimiento' => 'birthYear',
                            'pronombres_preferidos' => 'preferredPronouns',
                            'redes_sociales_de_ser_posible_pon_el_enlace_sino_nombre_y_la_red_social_a_la_que_corresponde_dicho_nombre' => 'socialMedia',
                            'teléfono' => 'phone',
                            'método_de_contacto_preferido' => 'preferredContactMethod',
                            'short_bio' => 'shortBio',
                            'biografía_larga23_párrafos' => 'longBio',
                            'tu_estudio_esta_abierto_al_público' => 'studioOpenToPublic',
                            'si_tu_estudio_esta_abierto_al_público_por_favor_incluye_la_dirección_yo_un_enlace_ó_las_instrucciones_de_visita' => 'studioAddressOrLink',
                            'te_pedimos_una_foto_de_los_hombros_para_arriba_esta_foto_sera_expuesta_junto_con_tu_biografía_en_la_app_del_proyecto' => 'photoUrl',
                            'aparte_de_español_qué_otras_lenguas_hablas' => 'otherLanguagesSpoken',
                            'incluye_todos_los_tipos_de_arte_que_realizas' => 'artTypes',
                            'un_slogan_ó_tagline_sobre_quién_eres_ej_artista_chiapaneco_arte_textil35_palabras_max' => 'tagline',
                            // patch the email to be snake case
                            // but also patch the row key "email" to use "Email Address" when not set
                            'email' => 'email',
                        ];

                        //set $row['code']  :  AsciiSlugger()->slug($name)->toString()
                        if (isset($row['name'])) {
                            $row['code'] = (new AsciiSlugger())->slug($row['name'])->toString();
                        } elseif (isset($row['nombre_completo_yo_artístico'])) {
                            $row['code'] = (new AsciiSlugger())->slug($row['nombre_completo_yo_artístico'])->toString();
                        } else {
                            $row['code'] = 'unknown';
                        }

                        //hard set email as code
                        if (isset($row['email']) && !empty($row['email'])) {
                            $row['code'] =  time() . uniqid();//$row['email'];
                        }
                        //update $row following the $columnsMapping
                        foreach ($columnsMapping as $oldKey => $newKey) {
                            if (isset($row[$oldKey])) {
                                $row[$newKey] = $row[$oldKey];
                            }
                        }

                        //patch row key "email" and use "Email Address" when not set
                        if (!isset($row['email']) && isset($row['Email Address'])) {
                            $row['email'] = $row['Email Address'];
                        }

                        //if $row is an empty array, skip it
                        if (empty($row)) {
                            continue;
                        }

                        if (!$entity = $this->entityManager->getRepository($entityClass)->findOneBy([
                            'email' => $row['email'],
                            ])
                        ) {
                            $entity = new $entityClass();
                            $entity->setEmail($row['email'] ?? null);
                            $entity->setCode($row['code']);
                            $this->entityManager->persist($entity);
                        }

                        foreach ($row as $var => $value) {
                            if ($value) {
                                try {

                                    $this->propertyAccessor->setValue($entity, $var, $value);
                                } catch (\Exception $e) {
                                    //dd($entity, $var, $value, $e->getMessage());
                                }
                            }
                        }

                        switch ($sheet) {
                            case 'DATOS ARTISTAS':
                                break;

                            default:
                                dd("Missing tab: " . $sheet);
                        }

                        //dump($row['email'] ?? $row['code'] ?? 'unknown');
                    }
                } catch (\Exception $e) {
                    //dd($csv, $e);
                    throw new \Exception(sprintf('Error processing sheet "%s": %s', $sheet, $e->getMessage()));
                }

                try {
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    // Handle or log the exception as needed
                    throw new \Exception(
                        'Error saving entities: ' . $e->getMessage() .
                        ' | Entity: ' . json_encode($entity->getCode() ?? 'new')
                    );
                }
            }
        );
            //dd($data);
//        $sheetService->downloadSheetToLocal('piezas', 'data/piezas.csv');
//        // integrate with Google Sheets
//        dd();
            return $this->render('app/index.html.twig', []);

        } catch (\Exception $e) {
            $this->logger->error('Error during sync process', [
                'message' => $e->getMessage(),
                'spreadsheetId' => $this->googleSpreadsheetId
            ]);

            return $this->render('app/index.html.twig', [
                'error' => 'Sync process failed: ' . $e->getMessage()
            ]);
        }
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
        }https://pgsc.wip/es/obj/fe1https://pgsc.wip/es/obj/fe1https://pgsc.wip/es/obj/fe1https://pgsc.wip/es/obj/fe1

        // Add audio code if present
        if ($audioCode = $obra->audioCode) {
            $allMediaCodes[] = $audioCode;
        }

        $mediaByCode = [];
        if (!empty($allMediaCodes)) {
            $mediaItems = $this->mediaRepository->createQueryBuilder('m')
                ->where('m.code IN (:codes)')
                ->setParameter('codes', array_unique($allMediaCodes))
                ->getQuery()
                ->getResult();

            foreach ($mediaItems as $media) {
                $mediaByCode[$media->getCode()] = $media;
            }
        }

        return [
            'obj' => $obra,
            'imagesByCode' => $mediaByCode, // Keep the same name for backward compatibility
            'mediaByCode' => $mediaByCode,  // New name for clarity
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
                ->where('m.code IN (:codes)')
                ->setParameter('codes', array_unique($allImageCodes))
                ->getQuery()
                ->getResult();

            foreach ($images as $image) {
                $imagesByCode[$image->getCode()] = $image;
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
        $obras = $artist->obras;https://pgsc.wip/es/obj/fe1

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
                ->where('m.code IN (:codes)')
                ->setParameter('codes', array_unique($allImageCodes))
                ->getQuery()
                ->getResult();

            foreach ($images as $image) {
                $imagesByCode[$image->getCode()] = $image;
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

    #[Route('/sais_audio_callback', name: 'sais_audio_callback')]
    public function saisAudioCallback(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('PGSC Audio callback received', [
            'data' => $data,
            'query' => $request->query->all(),
        ]);

        // Extract the SAIS code from the data or URL parameters
        $saisCode = $request->query->get('code') ?? $data['code'] ?? null;

        if (!$saisCode) {
            $this->logger->error('Audio callback: No SAIS code found in request', [
                'data' => $data,
                'query' => $request->query->all()
            ]);
            return new Response('No SAIS code found in request', Response::HTTP_BAD_REQUEST);
        }

        // Find the Media entity by SAIS code
        /** @var Media $media */
        if (!$media = $this->mediaRepository->find($saisCode)) {
            $this->logger->error('Audio callback: Media entity not found', [
                'saisCode' => $saisCode,
                'data' => $data,
                'query' => $request->query->all()
            ]);
            return new Response('Media entity not found for SAIS code: ' . $saisCode, Response::HTTP_NOT_FOUND);
        }

        // Update Media entity with SAIS processing results
        if (isset($data['statusCode'])) {
            $media->setStatusCode($data['statusCode']);
        }
        if (isset($data['mimeType'])) {
            $media->setMimeType($data['mimeType']);
        }
        if (isset($data['size'])) {
            $media->setSize($data['size']);
        }
        if (isset($data['blur'])) {
            $media->setBlur($data['blur']);
        }
        if (isset($data['context'])) {
            $media->setContext($data['context']);
        }
        if (isset($data['resized'])) {
            $media->resized = $data['resized'];
        }

        // Update the updated timestamp
        $media->setUpdatedAt(new \DateTimeImmutable());

        // Persist changes
        $this->entityManager->flush();

        $this->logger->info('Audio callback: Updated Media entity', [
            'saisCode' => $saisCode,
            'type' => $media->type,
            'statusCode' => $data['statusCode'] ?? null,
            'mimeType' => $data['mimeType'] ?? null,
            'size' => $data['size'] ?? null
        ]);

        return new Response(
            json_encode([
                'success' => true,
                'message' => 'Audio callback processed successfully',
                'saisCode' => $saisCode,
                'type' => $media->type,
                'statusCode' => $data['statusCode'] ?? null
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }

    #[Route('/webhook/media', name: 'app_media_webhook')]
    public function mediaWebhook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('PGSC Media webhook callback received', [
            'data' => $data,
            'query' => $request->query->all(),
        ]);

        // Extract the SAIS code from the data
        $saisCode = $data['code'] ?? null;

        if (!$saisCode) {
            $this->logger->error('Media webhook: No SAIS code found in request', [
                'data' => $data,
                'query' => $request->query->all()
            ]);
            return new Response('No SAIS code found in request', Response::HTTP_BAD_REQUEST);
        }

        // Create or update Media entity with SAIS data
        /** @var Media $image */
        if (!$image = $this->mediaRepository->find($saisCode)) {
            return $this->json([
                'error' => 'Media webhook: No SAIS code found in request',
            ]);
        }
        $image->resized = $data['resized'] ?? null;

        $this->logger->info('Media webhook: Updated Media entity', [
            'saisCode' => $saisCode,
            'hasResized' => !empty($data['resized']),
            'statusCode' => $data['statusCode'] ?? null
        ]);

        return new Response(
            json_encode([
                'success' => true,
                'message' => 'Media webhook processed successfully',
                'saisCode' => $saisCode,
                'imageProcessed' => !empty($data['resized'])
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }

    #[Route('/webhook/thumb', name: 'app_thumb_webhook')]
    public function thumbWebhook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('PGSC Thumb webhook callback received', [
            'data' => $data,
            'query' => $request->query->all(),
        ]);

        // Extract the SAIS image code from URL parameters or data
        $saisCode = $request->query->get('code') ?? $data['code'] ?? null;

        if (!$saisCode) {
            $this->logger->error('Thumb webhook: No SAIS code found in request', [
                'data' => $data,
                'query' => $request->query->all()
            ]);
            return new Response('No SAIS code found in request', Response::HTTP_BAD_REQUEST);
        }

        // Find the Media entity by SAIS code
        if (!$image = $this->mediaRepository->find($saisCode)) {
            $this->logger->error('Thumb webhook: Media entity not found', [
                'saisCode' => $saisCode,
                'data' => $data,
                'query' => $request->query->all()
            ]);
            return new Response('Media entity not found for SAIS code: ' . $saisCode, Response::HTTP_NOT_FOUND);
        }

        $liipCode = $data['liipCode']; // 'small', 'medium', 'large'
        $url = $data['url'];
        $image->resized[$liipCode] = $url;
        $this->entityManager->flush();

        return new Response(
            json_encode([
                'success' => true,
                'message' => 'Thumb webhook processed successfully',
                'saisCode' => $saisCode,
                'liipCode' => $data['liipCode'] ?? null,
                'resizedCount' => count($image->resized??[])
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
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
        $arguments = (array) new AccountSetup('rootdd', 1400);

        $result = $this->mcpClientService->callTool($client,
            'create_account',
            $arguments
        );

        dd($result, $tools);

        return new Response('JsonRPC Client created successfully: ' . get_class($client));
    }
}
