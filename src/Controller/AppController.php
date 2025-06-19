<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
use App\Form\ArtistFormType;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
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


#[Route('/{_locale}')]
final class AppController extends AbstractController
{
    public function __construct(
        private LocationRepository $locationRepository,
        private ArtistRepository $artistRepository,
        private ObraRepository $obraRepository,
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
        SheetService $sheetService,
        #[MapQueryParameter] bool $refresh = false,
    ): Response {

        //return a temp response
//        return new Response('Syncing...');
        $spreadsheet = $sheetService->getGoogleSpreadSheet($this->googleSpreadsheetId);
//        $x = $sheetService->downloadSheetToLocal('@artists', 'data/ardata.csv');
//        dd($x);

//        $accessor = new PropertyAccessor();
        $data = $sheetService->getData(
            $this->googleSpreadsheetId,
            $refresh,
            function ($sheet, $csv) {
                //save to local file filename : data/{spreadsheet_id}_{sheet}.csv
                $filePath = sprintf('%s/data/%s.csv', $this->getParameter('kernel.project_dir'),u($sheet)->snake()->toString());if (!is_dir('data')) {
                    mkdir('data', 0777, true);
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
    }

    #[Route('/home', name: 'app_homepage')]
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
        return [
            'obj' => $obra,
        ];
    }

    #[Route('/locations/{locationId}', name: 'location_show')]
    #[Template('location/show.html.twig')]
    public function showLocation(Location $location): Response|array
    {
        if ($location->getLat()) {
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

    #[Route('/admin/print-selected-obras', name: 'print_selected_obras')]
    public function indexLocation(): Response
    {
        dd();

        return $this->render('location/index.html.twig', [
            'locations' => $this->locationRepository->findAll(),
        ]);
    }

    //temp route sais_audio_callback
    #[Route('/sais_audio_callback', name: 'sais_audio_callback')]
    public function saisAudioCallback(): Response
    {
        // Handle the callback from SAIS for audio processing
        // You can access the request data and process it as needed
        // For example, you might want to log the data or update a database record

        /* request data sample 
        {"request":{"code":"8332ce209f443eb0"}}
        */

        //send temp log
        $this->logger->info('AMINE sais audio callback received', [
            'request' => $_REQUEST, // or $request->request->all() if using Symfony Request object
        ]);

        //log post data via logger
        $this->logger->info('AMINE SAIS audio callback POST data', [
            'post' => $_POST,
        ]);

        /* payload sample 

        {"json":{"mimeType":"video/mp4","size":3253320,"resized":[],"blur":null,"statusCode":200,"originalHeight":null,"originalWidth":null,"context":[],"root":"chijal","code":"8332ce209f443eb0","path":"chijal/0/8332ce209f443eb0.mp4","originalUrl":"https://drive.google.com/file/d/1pwvaIrBNc6XM_yaDUiNnkDnhiUptpZwZ/view?usp=sharing","marking":"downloaded"}}

        */

        //log the json payload if available
        $jsonPayload = file_get_contents('php://input');
        if ($jsonPayload) {
            $this->logger->info('AMINE SAIS audio callback JSON payload', [
                'json' => json_decode($jsonPayload, true),
            ]);
        } else {
            $this->logger->info('AMINE SAIS audio callback no JSON payload');
        }



        // For now, just return a simple response
        return new Response('SAIS audio callback received successfully');
    }

    //A Temp route to test  JsonRPC\Client;
    #[Route('/jsonrpc/test', name: 'jsonrpc_test')]
    public function jsonRpcTest(): Response
    {

        //prepare the http client from the jsonrpc pack
        $httpClient = new \JsonRPC\HttpClient(
            'https://sais.wip/tools',
        );

        //add curl proxy option 
        $httpClient->addOption(
            CURLOPT_PROXY,
            'http://127.0.0.1:7080'
        );

        // This is a temporary route to test JsonRPC\Client
        // You can use this to test the JsonRPC\Client functionality
        $client = new \JsonRPC\Client('https://sais.wip/tools', false, $httpClient);

        $arguments = new CreateUserSchema("root",1400);

        $result = $client->execute('tools/call', [
            'name' => 'create_account',
            'arguments' => (array) $arguments,
        ]);

        //call for tools list
        // $result = $client->execute('tools/list', [
        //     'root' => 'chijal',
        //     'limit' => 10,
        //     'offset' => 0,
        // ]);

        dd($result);

        return new Response('JsonRPC Client created successfully: ' . get_class($client));
    }
}
