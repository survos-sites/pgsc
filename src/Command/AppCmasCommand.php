<?php

namespace App\Command;

use App\Entity\Sacro;
use App\Repository\SacroRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

use function Symfony\Component\String\u;

#[AsCommand('app:cmas', 'Import CMAS data')]
final class AppCmasCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SacroRepository $sacroRepository,
        private SluggerInterface $asciiSlugger,
        private PropertyAccessorInterface $accessor,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    public function __invoke(
        IO $io,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Argument(description: 'path file downloaded csv')]
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

            dump("@todo: get a public link to $driveUrl or download it");
            // https://www.googleapis.com/drive/v3/files/FILE_ID?alt=media

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
        }
        $this->entityManager->flush();
        $io->success($this->getName() . ' success.');

        return self::SUCCESS;
    }
}
