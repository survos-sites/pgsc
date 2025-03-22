<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Location;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

final class AppController extends AbstractController
{

    public function __construct(
        private LocationRepository $locationRepository,
        private ArtistRepository $artistRepository,
    )
    {

    }
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
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
            'controller_name' => 'AppController',
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

    #[Route('/locations/{locationId}', name: 'location_show')]
    #[Template('location/show.html.twig')]
    public function showLocation(Location $location): Response|array
    {

        $myMap = (new Map());
        if ($location->getLat()) {
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
            'my_map' => $myMap,
            'location' => $location,
        ];
    }

}
