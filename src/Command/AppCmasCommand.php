<?php

namespace App\Command;

use App\Entity\Sacro;
use App\Repository\SacroRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\FlickrBundle\Services\FlickrService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

use function Symfony\Component\String\u;

#[AsCommand('app:cmas', 'Import CMAS data')]
final class AppCmasCommand
{

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        private EntityManagerInterface $entityManager,
        private SacroRepository $sacroRepository,
        private SluggerInterface $asciiSlugger,
        private PropertyAccessorInterface $accessor,
        private FlickrService $flickrService,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('', 'path file downloaded csv')]
        string $path = 'data/cmas.csv',
        #[Option(description: 'Process Google Drive images')]
        bool $images = false,
    ): int {

//        $path = $projectDir . '/data/cmas.csv';
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
            $sacro->setDriveUrl($driveUrl);

            $sacro->setExtra($extra);
            foreach (['es', 'en'] as $locale) {
                foreach (['label', 'description', 'notes'] as $field) {
                    $translate = $sacro->translate($locale);
                    $value = $row[$field . '.' . $locale];
                    if ('label' == $field) {
                        $value = u($value)->lower()->localeTitle('es')->title(true)->toString();
                    }
                    $this->accessor->setValue(
                        $translate,
                        $field,
                        $value
                    );
                }
            }
            $sacro->mergeNewTranslations();
            // this should be done in a transition, not during the load
            if ($flickrId = $sacro->getFlickrId()) {
                if (!$sacro->getFlickrInfo()) {
                    $info = $this->flickrService->photos()->getInfo($flickrId);
                    $sacro
                        ->setFlickrInfo($info)
                        ->setFlickrUrl($this->flickrService->flickrThumbnailUrl($info));
                }
            }
        }

        $this->entityManager->flush();
        $io->success(self::class . ' success: ' . $this->sacroRepository->count());

        return Command::SUCCESS;
    }
}
