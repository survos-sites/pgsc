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
        #[Autowire('%kernel.environment%')] protected string $env,
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
        #[Option(description: 'Process Google Drive images')]
        ?int $limit = null,
    ): int {
        $limit ??= $this->env === 'test' ? 3 : 100;

//        $path = $projectDir . '/data/cmas.csv';
        if (!file_exists($path)) {
            $io->error(sprintf('File "%s" does not exist', $path));
            return Command::FAILURE;
        }
        $reader = Reader::createFromPath($path, 'r');
        $reader->setHeaderOffset(0);
        $index = 0;
        foreach ($reader as $index => $row) {
            $extra = [];
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

            if ($index >= $limit) {
                break;
            }
        }

        $this->entityManager->flush();
        $io->success(self::class . ' success: ' . $index. ' total is now ' . $this->sacroRepository->count());

        return Command::SUCCESS;
    }
}
