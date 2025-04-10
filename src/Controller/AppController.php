<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
use App\Form\ArtistFormType;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService;
use Survos\GoogleSheetsBundle\Service\SheetService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

#[Route('/{_locale}')]
final class AppController extends AbstractController
{
    public function __construct(
        private LocationRepository $locationRepository,
        private ArtistRepository $artistRepository,
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    #[Route('/sync', name: 'app_sync')]
    public function sync(
        SheetService $sheetService,
        #[MapQueryParameter] bool $refresh = false,
    ): Response {
//        $accessor = new PropertyAccessor();
        $data = $sheetService->getData(
            'piezas',
            $refresh,
            function ($sheet, $csv) {
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
                    Location::class => 'email' // ??
                };
                $reader = Reader::createFromString($csv);
                $reader->setHeaderOffset(0);
                try {
                    foreach ($reader as $row) {
                        if (
                            !$entity = $this->entityManager->getRepository($entityClass)->findOneBy([
                            'email' => $row['email'],
                            ])
                        ) {
                            $entity = new $entityClass();
                            $entity->setEmail($email = $row['email']);
                            $entity->setCode(u($email)->before('@')->lower()->toString());
                            $this->entityManager->persist($entity);
                        }
                        foreach ($row as $var => $value) {
                            if ($value) {
                                try {
                                    $this->propertyAccessor->setValue($entity, $var, $value);
                                } catch (\Exception $e) {
                                    dd($entity, $var, $value, $e->getMessage());
                                }
                            }
                        }
                        switch ($sheet) {
                            case 'DATOS ARTISTAS':
                                break;

                            default:
                                dd("Missing tab: " . $sheet);
                        }
                    }
                } catch (\Exception $e) {
                    dd($csv, $e);
                }
                $this->entityManager->flush();
            }
        );
        dd($data);
        $sheetService->downloadSheetToLocal('piezas', 'data/piezas.csv');
        // integrate with Google Sheets
        dd();
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
}
