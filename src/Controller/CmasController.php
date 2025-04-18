<?php

namespace App\Controller;

use App\Entity\Sacro;
use App\Repository\SacroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\ServiceUsage\GoogleApiService;
use League\Csv\Reader;
use Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService;
use Survos\GoogleSheetsBundle\Service\SheetService;
use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
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
        private SheetService $sheetService,
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
    public function images(SaisClientService $saisClientService): Response|array
    {
        foreach ($this->sacroRepository->findBy([], [], null) as $sacro) {
            $flickr = $sacro->getExtra()['flickr'];
            if ($sacro->getDriveUrl()) {
                $result = $saisClientService->dispatchProcess(
                    new ProcessPayload('sacro', [
                        $sacro->getDriveUrl()
                    ])
                );
                $sacro->setImageSizes($result[0]['resized']??[]);
            }
        }
        $this->entityManager->flush();
        return $this->redirectToRoute('cmas_index');
    }

    #[Route('/cmas/import', name: 'cmas_import')]
    #[Template('cmas/index.html.twig')]
    public function import(
        GoogleSheetsApiService $sheetService,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): Response|array {

        dd("bin/console app:cmas OR use this to test import with API?");

        // using the api instead of downloading.
        //        return
        $id = '1PnSESwWyJQI7T6L8g94zQzMFA0vITkGeWqOkGT7pxnw';
//        $id = '10MBxAwPuCuC8o4EwzYxc2-0ziRCMxB09W750rVnGc6M'; // COPY of Cmas

//        $sheetService->setSheetServices($id);

        $spreadsheet = $this->sheetService->getGoogleSpreadSheet($id);
        foreach ($spreadsheet->getSheets() as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            $data = $sheet->getData();
            dd($data, $sheet, $title);
        }

        return $this->render('cmas/index.html.twig', [
        ]);
    }

}
