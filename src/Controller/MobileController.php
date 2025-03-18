<?php

// we use this to have a standardized way of creating the paths
// for the mobile controller.

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MobileController extends AbstractController
{
    #[Route('/obra/{obraId}', name: 'mobile_obra')]
    #[Route('/artist/{artistId}', name: 'mobile_artist')]
    #[Route('/locations/{locationId}', name: 'location_obra')]
    public function mobileRoute(): Response|array
    {
        return [];
    }
}
