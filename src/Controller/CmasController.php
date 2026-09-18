<?php

namespace App\Controller;

use App\Entity\Sacro;
use App\Repository\SacroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\ServiceUsage\GoogleApiService;
use League\Csv\Reader;
use Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService;
use Survos\GoogleSheetsBundle\Service\SheetService;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

use function Symfony\Component\String\u;

#[Route('/{_locale}')]
final class CmasController extends AbstractController
{
    public function __construct(
        private SluggerInterface $asciiSlugger,
        private EntityManagerInterface $entityManager,
        private SacroRepository $sacroRepository,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    #[Route('/cmas', name: 'cmas_index', methods: ['GET'])]
    #[Template('cmas/index.html.twig')]
    public function index(): Response|array
    {
        return [
            'sacros' => $this->sacroRepository->findAll(),
        ];
    }

    #[Route('/cmas-images', name: 'cmas_images', methods: ['GET'])]
    #[Template('cmas/index.html.twig')]
    public function images(): Response|array
    {
        // @todo re-implement via media-bundle dispatch when Sacro images are migrated
        return $this->redirectToRoute('cmas_index');
    }

    #[Route('/cmas/import', name: 'cmas_import')]
    #[Template('cmas/index.html.twig')]
    public function import(): Response
    {
        return new Response('Importing...');
    }
}
