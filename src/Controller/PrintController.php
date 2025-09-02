<?php

namespace App\Controller;

use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class PrintController extends AbstractController
{
    public function __construct(
        private ObraRepository $obraRepository,
        private LocationRepository $locationRepository,
        private ArtistRepository $artistRepository,
    )
    {
    }

    #[Route('/labels/{shortClass}', name: 'print_labels')]
    public function labels(
        string $shortClass,
        #[MapQueryParameter('loc')] ?string $locationCodes=null,
    ): Response
    {
//        $locationCodes = $locationCodes ? explode(',', $locationCodes):[];
        // hack
        if ($locationCodes) {
            $obras = $this->locationRepository->find($locationCodes)->obras;
        } else {
            $obras = $this->obraRepository->findAll();
        }
        $entities = match ($shortClass) {
            'obra' => $obras,
            'location' => $this->locationRepository->findAll(),
            'artists' => $this->artistRepository->findAll(),
        };
        return $this->render("print/obras.html.twig", [
            'entities' => $entities,
            'obras' => $entities,
            'locations' => $entities,
            'artists' => $entities,
        ]);
    }
}
