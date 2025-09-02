<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\AltosObj;
use App\Entity\Loc;
use App\Repository\AltosObjRepository;
use App\Service\GoogleDocParserService;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\CoreBundle\Service\SurvosUtils;
use Survos\GoogleSheetsBundle\Service\SheetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('loc:import:gdoc', 'Import hierarchical locations from a Google Doc (headings → nodes, paragraphs → descriptions)')]
final class LocImportGoogleDocCommand extends Command
{
    public function __construct(
        private readonly GoogleDocParserService $parser,
        private readonly EntityManagerInterface $em,
        private SheetService                    $sheetService,
        private readonly AltosObjRepository $altosObjRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle                                                    $io,
        #[Argument('Google Doc URL or ID')] string                      $documentUrl = 'https://docs.google.com/document/d/18TqaKGaSkjjAveE4lgoSjUjbBMRUCBwkaIG7fswYstc/',
        #[Option('Default type when not detected from heading')] string $defaultType = 'location',
        #[Option('Truncate Loc table before import')] ?bool             $truncate = null,
        #[Option('Dry run (parse only, no DB writes)')] ?bool           $dryRun = null,
        #[Option('Limit nodes (debug)')] ?int                           $limit = null,
    ): int
    {
        $this->spreadsheet();
        $io->title('Loc Import from Google Doc');

        // Parse Google Doc → tree structure
        $result = $this->parser->parseDocument($documentUrl);
        $meta = $result['metadata'] ?? [];
        $items = $result['structure'] ?? [];

        $io->writeln(sprintf('Doc: %s (id=%s, rev=%s)', $meta['title'] ?? 'n/a', $meta['id'] ?? 'n/a', $meta['revision'] ?? 'n/a'));

        if ($truncate) {
            if ($dryRun) {
                $io->note('Truncate requested but dry-run is enabled (skipping).');
            } else {
                $this->em->createQuery('DELETE FROM App\Entity\Loc l')->execute();
                $io->success('Truncated Loc table.');
            }
        }

        $created = 0;

        // Build tree recursively
        $created += $this->persistFromParsed($items, null, $defaultType, $limit, $dryRun);

        if ($dryRun) {
            $io->success(sprintf('[DRY RUN] Would create %d Loc nodes.', $created));
            return Command::SUCCESS;
        }

        $this->em->flush();
        $io->success(sprintf('Created %d Loc nodes.', $created));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string,mixed>> $items
     */
    private function persistFromParsed(
        array   $items,
        ?Loc    $parent,
        ?string $defaultType,
        ?int    &$remainingLimit,
        ?bool   $dryRun
    ): int
    {
        $count = 0;

        foreach ($items as $item) {
            // Headings / structural nodes have a numeric level; paragraphs have level === null
            $isHeading = array_key_exists('level', $item) && $item['level'] !== null;

            if (!$isHeading) {
                // Top-level loose paragraphs (should be rare) are ignored; they belong under a heading
                continue;
            }

            if ($remainingLimit !== null && $remainingLimit <= 0) {
                break;
            }

            $node = new Loc();
            $node->type = $this->resolveType($item, $defaultType);
            $node->label = $this->cleanTitle($item['title'] ?? '');
            $node->description = $this->collectBodyMarkdown($item['children'] ?? []);

            if ($parent) {
                $node->setParent($parent);
            }

            if (!$dryRun) {
                $this->em->persist($node);
            }

            $count++;
            if ($remainingLimit !== null) {
                $remainingLimit--;
            }

            // Recurse into heading-children (skip paragraph children)
            $childHeadings = $this->filterHeadingChildren($item['children'] ?? []);
            if ($childHeadings) {
                $count += $this->persistFromParsed($childHeadings, $node, $defaultType, $remainingLimit, $dryRun);
            }
        }

        return $count;
    }

    /**
     * Children where level === null are body paragraphs: concatenate as Markdown.
     * We keep their original text with blank lines between paragraphs.
     *
     * @param array<int, array<string,mixed>> $children
     */
    private function collectBodyMarkdown(array $children): ?string
    {
        $parts = [];
        foreach ($children as $child) {
            $isHeading = array_key_exists('level', $child) && $child['level'] !== null;
            if ($isHeading) {
                continue;
            }
            $text = trim((string)($child['title'] ?? ''));
            if ($text !== '') {
                $parts[] = rtrim($text);
            }
        }

        if (!$parts) {
            return null;
        }

        // Two newlines between paragraphs to preserve Markdown paragraphs
        return implode("\n\n", $parts);
    }

    /**
     * Keep only structural children (level !== null) to recurse into.
     *
     * @param array<int, array<string,mixed>> $children
     * @return array<int, array<string,mixed>>
     */
    private function filterHeadingChildren(array $children): array
    {
        return array_values(array_filter($children, static function ($c): bool {
            return array_key_exists('level', $c) && $c['level'] !== null;
        }));
    }

    private function resolveType(array $item, ?string $defaultType): string
    {
        $type = (string)($item['type'] ?? '');
        if ($type !== '') {
            return $type;
        }

        // Infer from code prefix if available
        $code = (string)($item['location_code'] ?? '');
        if ($code !== '') {
            if (str_starts_with($code, 'SALA-')) {
                return 'sala';
            }
            if (str_starts_with($code, 'VIT-')) {
                return 'vitrina';
            }
            if (str_starts_with($code, 'POS-')) {
                return 'ficha_descriptiva';
            }
        }

        return $defaultType ?: 'location';
        // (You could also map from level if you want a fallback: 0=sala, 1=dialogo, 2=vitrina, etc.)
    }

    /**
     * Remove codes like "SALA-1: " / "VIT-1-2: " / "POS-..." from the visible label.
     */
    private function cleanTitle(string $title): string
    {
        $title = preg_replace('/^(SALA-\d+|VIT-\d+-\d+|POS-\d+-\d+-\d+):\s*/', '', $title) ?? $title;
        return trim($title);
    }

    private function spreadsheet(): void
    {
        foreach ($this->entityManager->getRepository(Loc::class)->findAll() as $loc) {
            $locs[$loc->code] = $loc;
        }
        $url = 'https://docs.google.com/spreadsheets/d/11L8LQfMKs9t0HAWF-Y_ApyVsIzPJGjgwx1MhvGQV1KU/edit?gid=595884644#gid=595884644';

        ['spreadsheetId' => $id, 'gid' => $gid] = $this->extractSpreadsheetIdAndGid($url);

        $sheetService = $this->sheetService;

        // If your bundle’s API wants an ID:
        $spreadsheet = $sheetService->getGoogleSpreadSheet($id);

        $refresh = true;

        // If your bundle exposes per-sheet CSV via callback, keep it.
        $data = $sheetService->getData(
            $id,
            $refresh,
            function (string $sheetName, string $csv, array $values) use ($id, $locs    ): void {
                // save to local file: data/{spreadsheetId}_{sheet}.csv
                $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $sheetName) ?: 'sheet';
                $path = sprintf('data/losaltos/%s.csv', $safe);
                if ($safe === 'exhibition') {
                    $reader = Reader::createFromString($csv, 'r');
                    $reader->setHeaderOffset(0);
                    foreach ($reader as $row) {
                        $ubiCode = $row['ubi'];
                        if (!$ubiCode) { continue; }
                        dd($ubiCode);
                        if ($loc = $locs[$ubiCode] ?? null) {
                            SurvosUtils::assertKeyExists('code*', $row);
                            $code = $row['code*'];
                            if (!$altosObj  = $this->altosObjRepository->findOneBy(['code' => $code])) {
                                $altosObj = new AltosObj();
                                $this->entityManager->persist($altosObj);
                                $altosObj->code = $code;
                                $loc->addAltosObj($altosObj);
                            }
                            $altosObj->title_es = $row['title.es*'];
                            $altosObj->title_tl = $row['title.tl*'];
                            $altosObj->title_tzh = $row['title.tzh*'];
                            $altosObj->description = $row['description*'];
                            foreach (['ubi'] as $prop) {
                                $altosObj->$prop = $row[$prop];
                            }
//                            dd($row);
//                            dd($altosObj->name, $row['title.es*'], $row['ubi'], $loc->code, $code);
                        }
                    }
                }
                $this->entityManager->flush();
                @mkdir(dirname($path), 0777, true);
                file_put_contents($path, $csv);
            }
        );

        // $data may already be parsed rows depending on your bundle.
        // dd($data);
    }

    private function extractSpreadsheetIdAndGid(string $urlOrId): array
    {
        // Already an ID?
        if (preg_match('/^[a-zA-Z0-9-_]+$/', $urlOrId)) {
            return ['spreadsheetId' => $urlOrId, 'gid' => null];
        }

        $spreadsheetId = null;
        $gid = null;

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $urlOrId, $m)) {
            $spreadsheetId = $m[1];
        }
        if (preg_match('#[?&]gid=([0-9]+)#', $urlOrId, $m)) {
            $gid = $m[1];
        }

        if (!$spreadsheetId) {
            throw new \InvalidArgumentException('Could not extract spreadsheetId from URL/ID: ' . $urlOrId);
        }

        return ['spreadsheetId' => $spreadsheetId, 'gid' => $gid];
    }

}
