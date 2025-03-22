<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Location;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

final class AppController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
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
