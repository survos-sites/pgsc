<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\AltosObj;
use App\Entity\Loc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('altos:import:csv', 'Import Altos objects from a CSV (local file or Google Sheets URL/ID) and link to Loc by code')]
final class AltosObjImportCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        // Your bundle’s service (rename the type to your actual service)
        private readonly \Survos\GoogleSheetsBundle\Service\SheetService $sheetService,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'CSV path or Google Sheets URL/ID')] string $source,
        #[Option(description: 'If using Google Sheets, optional gid of the tab to import')] ?string $gid = null,
        #[Option(description: 'Dry run (parse only)')] ?bool $dryRun = null,
        #[Option(description: 'Truncate AltosObj table before import')] ?bool $truncate = null,
        #[Option(description: 'Batch size for flush')] ?int $batch = 200,
    ): int {
        $io->title('Altos CSV Import');

        // Resolve source → CSV text
        $csv = $this->loadCsv($source, $gid, $io);

        if ($truncate) {
            if ($dryRun) {
                $io->note('Truncate requested but dry-run is enabled (skipping).');
            } else {
                $this->em->createQuery('DELETE FROM App\Entity\AltosObj a')->execute();
                $io->success('Truncated AltosObj table.');
            }
        }

        [$headers, $rows] = $this->parseCsv($csv);
        if (!$headers) {
            $io->error('No headers found in CSV.');
            return Command::FAILURE;
        }

        $io->writeln(sprintf('Columns: %s', implode(', ', $headers)));

        // Preload Loc by code for fast linking
        $locByCode = $this->loadLocIndex();

        $created = 0; $updated = 0; $linked = 0; $unlinked = 0;
        $i = 0;

        foreach ($rows as $row) {
            $i++;

            $data = $this->normalizeRow($headers, $row);

            $code = (string)($data['code*'] ?? $data['code'] ?? '');
            if ($code === '') {
                $io->warning("Row $i: missing code*, skipping");
                continue;
            }

            /** @var AltosObj|null $obj */
            $obj = $this->em->getRepository(AltosObj::class)->findOneBy(['code' => strtoupper(trim($code))]);

            $isNew = false;
            if (!$obj) {
                $obj = new AltosObj();
                $isNew = true;
            }

            // Map fields (use existing names from the entity)
            $obj->cons              = $this->toIntOrNull($data['cons!'] ?? null);
            $obj->code              = $code;
            $obj->museoProcedencia  = $data['museo de procedencia'] ?? ($data['museo_de_procedencia'] ?? null);
            $obj->location          = $data['location'] ?? null;
            $obj->location2         = $data['location 2'] ?? ($data['location_2'] ?? null);
            $obj->title_es          = $data['title.es*'] ?? ($data['title_es'] ?? null);
            $obj->title_tzh         = $data['title.tzh*'] ?? ($data['title_tzh'] ?? null);
            $obj->title_tl          = $data['title.tl'] ?? null;
            $obj->description       = $data['description*'] ?? ($data['description'] ?? null);
            $obj->description_tzh   = $data['description.tzh*'] ?? ($data['description_tzh'] ?? null);
            $obj->tecnica           = $data['tecnica'] ?? null;
            $obj->medidas           = $data['medidas'] ?? null;
            $obj->image             = $data['image*'] ?? ($data['image'] ?? null);
            $obj->image_s3          = $data['image.s3*'] ?? ($data['image_s3'] ?? null);
            $obj->fotografia        = $data['fotografía'] ?? ($data['fotografia'] ?? null);
            $obj->value_display     = $data['value!'] ?? ($data['value'] ?? null);

            // Link to Loc
            $ubiRaw = $data['ubi'] ?? null;
            $obj->ubi = $ubiRaw;

            $loc = $this->resolveLoc($ubiRaw, $locByCode);
            if ($loc) {
                $obj->loc = $loc;
                $linked++;
            } else {
                $obj->loc = null;
                $unlinked++;
            }

            if (!$dryRun) {
                $this->em->persist($obj);
            }

            if ($isNew) { $created++; } else { $updated++; }

            if (!$dryRun && ($i % max(1, (int)$batch) === 0)) {
                $this->em->flush();
                $this->em->clear(AltosObj::class); // keep memory stable
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf(
            '%d created, %d updated. Linked: %d, Unlinked: %d.',
            $created, $updated, $linked, $unlinked
        ));

        if ($unlinked > 0) {
            $io->warning('Some rows could not be linked to Loc (check `ubi` column and Loc.code).');
        }

        return Command::SUCCESS;
    }

    // ---------- helpers ----------

    private function loadCsv(string $source, ?string $gid, SymfonyStyle $io): string
    {
        // Sheets URL or bare ID?
        if (str_contains($source, 'docs.google.com/spreadsheets') || preg_match('/^[a-zA-Z0-9-_]+$/', $source)) {
            ['spreadsheetId' => $spreadsheetId, 'gid' => $gidFromUrl] = $this->extractSpreadsheetIdAndGid($source);
            $gid = $gid ?? $gidFromUrl;

            // Use your bundle’s API. Adjust to your actual method signatures.
            // Example pattern: getData($spreadsheetId, $refresh, $callbackPerTab)
            $buffer = null;
            $this->sheetService->getData($spreadsheetId, true, function (string $sheetName, string $csv, ?string $sheetGid = null) use (&$buffer, $gid, $io) {
                if ($gid === null || $sheetGid === (string)$gid) {
                    $buffer = $csv;
                }
            });

            if ($buffer === null) {
                throw new \RuntimeException('Could not retrieve CSV from the specified spreadsheet/tab.');
            }
            $io->writeln(sprintf('Fetched CSV from spreadsheetId=%s gid=%s', $spreadsheetId, $gid ?? 'n/a'));

            return $buffer;
        }

        // Local file
        if (!is_file($source)) {
            throw new \InvalidArgumentException('CSV file not found: ' . $source);
        }
        return file_get_contents($source) ?: '';
    }

    private function extractSpreadsheetIdAndGid(string $urlOrId): array
    {
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

    /**
     * @return array{0: string[], 1: array<int, string[]>}
     */
    private function parseCsv(string $csv): array
    {
        $rows = [];
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $csv);
        rewind($fp);

        while (($data = fgetcsv($fp)) !== false) {
            $rows[] = $data;
        }
        fclose($fp);

        if (!$rows) {
            return [[], []];
        }

        // First row = headers
        $headers = array_map([$this,'normalizeHeader'], array_shift($rows));

        return [$headers, $rows];
    }

    private function normalizeHeader(string $h): string
    {
        $h = trim($h);
        $h = str_replace(['*','!'], '', $h);         // drop required markers
        $h = preg_replace('/\s+/', ' ', $h);         // collapse spaces
        return mb_strtolower($h);                    // normalize
    }

    /**
     * @param string[] $headers
     * @param string[] $row
     * @return array<string, string|null>
     */
    private function normalizeRow(array $headers, array $row): array
    {
        $out = [];
        foreach ($headers as $i => $key) {
            $out[$key] = array_key_exists($i, $row) ? (is_string($row[$i]) ? trim($row[$i]) : $row[$i]) : null;
        }
        return $out;
    }

    /**
     * Preload Loc records and index by code (UPPER).
     * @return array<string, Loc>
     */
    private function loadLocIndex(): array
    {
        $all = $this->em->getRepository(Loc::class)->createQueryBuilder('l')->getQuery()->getResult();
        $index = [];
        /** @var Loc $loc */
        foreach ($all as $loc) {
            $code = $loc->code ?? null;
            if ($code) {
                $index[strtoupper($code)] = $loc;
            }
        }
        return $index;
    }

    private function resolveLoc(?string $ubi, array $locByCode): ?Loc
    {
        if ($ubi === null || $ubi === '') {
            return null;
        }
        $candidate = strtoupper(trim($ubi));

        // Exact match
        if (isset($locByCode[$candidate])) {
            return $locByCode[$candidate];
        }

        // Heuristics:
        // - convert "vit-20" → "VIT-20"
        if (preg_match('/^VIT[-\s_]?(\d+)$/i', $ubi, $m)) {
            $alt = 'VIT-' . $m[1];
            if (isset($locByCode[$alt])) {
                return $locByCode[$alt];
            }
        }

        // add more mappings here if your codes have a known pattern

        return null;
    }

    private function toIntOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (int)$v;
        return null;
    }
}
