<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Obra;
use App\Repository\LocationRepository;
use Survos\MediaBundle\Entity\BaseMedia;
use Survos\MediaBundle\Service\MediaUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes Chijal (pgsc's Location/Obra data) in TourForge's tourforge.json shape, so it can be
 * fetched by `sos`'s tourforge:fetch the same way as the Florence Navigator / FMU Campus Tour
 * sources -- see sos's docs/tourforge-integration.md. One Location = one stop (a physical place);
 * its Obras are folded into that stop's gallery/narration/description, since Location itself
 * carries no content of its own.
 */
final class TourForgeController extends AbstractController
{
    private const TOUR_ID = 'chijal-san-cristobal';

    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {
    }

    #[Route('/tourforge.json', name: 'tourforge_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $assets = [];
        $route = [];

        foreach ($this->locationRepository->findAll() as $location) {
            if ($location->lat === null || $location->lng === null) {
                continue; // TourForge stops require coordinates
            }

            $obras = $location->obras;
            $galleryNames = [];
            $narrationName = null;
            $descParts = [];

            foreach ($obras as $obra) {
                foreach ($obra->images as $image) {
                    $name = $this->registerAsset($assets, $image, 'image');
                    $galleryNames[] = $name;
                }
                if ($narrationName === null && $obra->audio instanceof BaseMedia) {
                    $narrationName = $this->registerAsset($assets, $obra->audio, 'audio');
                }
                if ($obra->title || $obra->description) {
                    $descParts[] = trim(($obra->title ?? '') . ($obra->description ? ': ' . $obra->description : ''));
                }
            }

            $route[] = array_filter([
                'type' => 'stop',
                'id' => $location->code,
                'title' => $location->name ?? $location->code,
                'desc' => implode("\n\n", $descParts),
                'lat' => $location->lat,
                'lng' => $location->lng,
                'trigger_radius' => 30.0,
                'narration' => $narrationName,
                'gallery' => array_values(array_unique($galleryNames)),
            ], static fn ($v) => $v !== null && $v !== '' && $v !== []);
        }

        $project = [
            'title' => 'Chijal',
            'assets' => $assets,
            'tours' => [
                [
                    'id' => self::TOUR_ID,
                    'title' => 'Chijal — Popup Galleries of San Cristóbal',
                    'desc' => 'Community art galleries and cultural sites around San Cristóbal de las Casas.',
                    'type' => 'walking',
                    'gallery' => [],
                    'path' => '',
                    'tiles' => null,
                    'route' => $route,
                    'pois' => [],
                ],
            ],
        ];

        return new JsonResponse($project);
    }

    #[Route('/tourforge/asset/{hash}', name: 'tourforge_asset', methods: ['GET'])]
    public function asset(string $hash): RedirectResponse
    {
        return new RedirectResponse($this->mediaUrlGenerator->resize($hash, MediaUrlGenerator::PRESET_LARGE));
    }

    /**
     * @param array<string, array{alt: string, attrib: string, type: string, hash: string}> $assets
     */
    private function registerAsset(array &$assets, BaseMedia $media, string $type): string
    {
        $name = $media->id;
        $assets[$name] ??= [
            'alt' => '',
            'attrib' => '',
            'type' => $type,
            'hash' => $media->id,
        ];

        return $name;
    }
}
