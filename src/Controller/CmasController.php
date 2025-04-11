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
    public function images(): Response|array
    {
        foreach ($this->sacroRepository->findAll() as $sacro) {
            $flickr = $sacro->getExtra()['flickr'];

            dd($sacro->getExtra());
        }
        return $this->redirectToRoute('cmas_index');
    }

    #[Route('/cmas/import', name: 'cmas_import')]
    #[Template('cmas/index.html.twig')]
    public function import(
        GoogleSheetsApiService $sheetService,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): Response|array {
        $path = $projectDir . '/data/cmas.csv';
        assert(file_exists($path), $path . ' does not exist');
        $reader = Reader::createFromPath($path, 'r');
        $reader->setHeaderOffset(0);
        foreach ($reader as $index => $row) {
            foreach ($row as $column => $value) {
                // dots mean translation
                if (!str_contains($column, '.')) {
                    $column = $this->asciiSlugger->slug($column)->lower()->toString();
                    $extra[$column] = $value;
                }
            }
            $code = $extra['code'];
            if (!$sacro = $this->sacroRepository->find($code)) {
                $sacro = new Sacro($code);
                $this->entityManager->persist($sacro);
                $this->entityManager->flush();
            }
            $driveUrl = $extra['vinculo'];

            dump("@todo: get a public link to $driveUrl or download it");
            // https://www.googleapis.com/drive/v3/files/FILE_ID?alt=media

            $sacro->setExtra($extra);
            foreach (['es', 'en'] as $locale) {
                foreach (['description', 'notes'] as $field) {
                    $translate = $sacro->translate($locale);
                    $this->accessor->setValue(
                        $translate,
                        $field,
                        $row[$field . '.' . $locale]
                    );
                }
            }
            $sacro->mergeNewTranslations();
        }
        $this->entityManager->flush();
        return $this->redirectToRoute('cmas_index');

        // for debugging
        return [
            'sacros' => $sacroRepository->findAll(),
        ];

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
