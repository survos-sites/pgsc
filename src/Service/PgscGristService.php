<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Survos\RecordStoreBundle\Adapter\Grist\GristClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PgscGristService
{
    /**
     * Choice colors for Locations.Status. Grist renders the cell with these, so the
     * status reads at a glance instead of being one more text column to squint at.
     */
    private const string STATUS_WIDGET_OPTIONS = '{"choices":["activo","inactivo"],"choiceOptions":{"activo":{"fillColor":"#D7F0DB","textColor":"#12683A"},"inactivo":{"fillColor":"#EDEDED","textColor":"#6B6B6B"}}}';

    private const array TABLES = [
        'Artists' => [
            ['id' => 'Code', 'fields' => ['type' => 'Text', 'label' => 'Code']],
            ['id' => 'Name', 'fields' => ['type' => 'Text', 'label' => 'Name']],
            ['id' => 'BirthYear', 'fields' => ['type' => 'Int', 'label' => 'Birth year']],
            ['id' => 'Email', 'fields' => ['type' => 'Text', 'label' => 'Email']],
            ['id' => 'Phone', 'fields' => ['type' => 'Text', 'label' => 'Phone']],
            ['id' => 'Social', 'fields' => ['type' => 'Text', 'label' => 'Social links']],
            ['id' => 'Bio', 'fields' => ['type' => 'Text', 'label' => 'Biography']],
            ['id' => 'Tagline', 'fields' => ['type' => 'Text', 'label' => 'Tagline']],
            // Photo bytes land in Hetzner S3 (see GRIST_DOCS_MINIO_* on the grist app),
            // keyed by content hash. DriveUrl stays as the legacy pointer coming from
            // the spreadsheet -- it is still what MediaRegistry hashes for identity.
            ['id' => 'Photo', 'fields' => ['type' => 'Attachments', 'label' => 'Photo']],
            ['id' => 'DriveUrl', 'fields' => ['type' => 'Text', 'label' => 'Image / Drive URL']],
            ['id' => 'YoutubeUrl', 'fields' => ['type' => 'Text', 'label' => 'YouTube URL']],
        ],
        'Locations' => [
            ['id' => 'Code', 'fields' => ['type' => 'Text', 'label' => 'Code']],
            ['id' => 'Name', 'fields' => ['type' => 'Text', 'label' => 'Name']],
            ['id' => 'Status', 'fields' => ['type' => 'Choice', 'label' => 'Status', 'widgetOptions' => self::STATUS_WIDGET_OPTIONS]],
            ['id' => 'Barrio', 'fields' => ['type' => 'Text', 'label' => 'Barrio']],
            ['id' => 'Address', 'fields' => ['type' => 'Text', 'label' => 'Address']],
            ['id' => 'Type', 'fields' => ['type' => 'Text', 'label' => 'Type']],
            ['id' => 'ContactName', 'fields' => ['type' => 'Text', 'label' => 'Contact name']],
            ['id' => 'Phone', 'fields' => ['type' => 'Text', 'label' => 'Phone']],
            ['id' => 'Latitude', 'fields' => ['type' => 'Numeric', 'label' => 'Latitude']],
            ['id' => 'Longitude', 'fields' => ['type' => 'Numeric', 'label' => 'Longitude']],
        ],
        'Obras' => [
            ['id' => 'Code', 'fields' => ['type' => 'Text', 'label' => 'Code']],
            ['id' => 'Artist', 'fields' => ['type' => 'Ref:Artists', 'label' => 'Artist']],
            ['id' => 'Location', 'fields' => ['type' => 'Ref:Locations', 'label' => 'Location']],
            ['id' => 'Exhibition', 'fields' => ['type' => 'Text', 'label' => 'Exhibition']],
            ['id' => 'Title', 'fields' => ['type' => 'Text', 'label' => 'Title']],
            ['id' => 'Description', 'fields' => ['type' => 'Text', 'label' => 'Description']],
            ['id' => 'Year', 'fields' => ['type' => 'Int', 'label' => 'Year']],
            ['id' => 'Width', 'fields' => ['type' => 'Int', 'label' => 'Width (cm)']],
            ['id' => 'Height', 'fields' => ['type' => 'Int', 'label' => 'Height (cm)']],
            ['id' => 'Depth', 'fields' => ['type' => 'Int', 'label' => 'Depth (cm)']],
            ['id' => 'Materials', 'fields' => ['type' => 'Text', 'label' => 'Materials']],
            ['id' => 'Price', 'fields' => ['type' => 'Numeric', 'label' => 'Price']],
            ['id' => 'Type', 'fields' => ['type' => 'Text', 'label' => 'Type']],
            ['id' => 'Size', 'fields' => ['type' => 'Text', 'label' => 'Size']],
            ['id' => 'Photo', 'fields' => ['type' => 'Attachments', 'label' => 'Photo']],
            ['id' => 'DriveUrl', 'fields' => ['type' => 'Text', 'label' => 'Image / Drive URL']],
            ['id' => 'YoutubeUrl', 'fields' => ['type' => 'Text', 'label' => 'YouTube URL']],
            ['id' => 'AudioCode', 'fields' => ['type' => 'Text', 'label' => 'Audio code']],
        ],
    ];

    private const array FORMS = [
        'Artists' => [
            'title' => 'Artist intake',
            'intro' => 'Add or update an artist. Use a short, stable code such as maria-lopez; artworks will link to this artist.',
            'fields' => ['Code', 'Name', 'Email', 'Phone', 'BirthYear', 'Social', 'Tagline', 'Bio', 'DriveUrl', 'YoutubeUrl'],
        ],
        'Locations' => [
            'title' => 'Location intake',
            'intro' => 'Add a studio, gallery, business, or exhibition location. Artworks can then select it from a searchable list.',
            'fields' => ['Code', 'Name', 'Type', 'Status', 'Barrio', 'Address', 'ContactName', 'Phone', 'Latitude', 'Longitude'],
        ],
        'Obras' => [
            'title' => 'Artwork intake',
            'intro' => 'Add an artwork and connect it to an existing artist and, when known, a location. The selectors show names while storing real references.',
            'fields' => ['Code', 'Title', 'Artist', 'Location', 'Exhibition', 'Type', 'Year', 'Materials', 'Size', 'Width', 'Height', 'Depth', 'Price', 'Description', 'DriveUrl', 'YoutubeUrl'],
        ],
    ];

    public function __construct(
        private GristClient $grist,
        private SyncService $source,
        private ArtistRepository $artists,
        private LocationRepository $locations,
        private ObraRepository $obras,
        #[Autowire('%env(GRIST_DOC_ID)%')] private string $documentId,
        #[Autowire('%env(GRIST_HOST)%')] private string $host,
    ) {
    }

    #[AsCommand('app:grist:sync', 'Provision PGSC Grist tables and copy normalized Google Sheets data')]
    public function sync(SymfonyStyle $io, #[Option('Refresh data from Google Sheets before exporting')] bool $refresh = false): int
    {
        if ($refresh) {
            $counts = $this->source->sync(true);
            $io->writeln(sprintf('Google Sheets normalized: %d artists, %d locations, %d obras.', $counts['artists'], $counts['locations'], $counts['obras']));
        }

        $this->provisionTables();
        $this->configureRelations();
        $io->writeln('Uploading artists…');
        $artistIds = $this->upsert('Artists', array_map($this->artistFields(...), $this->artists->findAll()));
        $io->writeln('Uploading locations…');
        $locationIds = $this->upsert('Locations', array_map($this->locationFields(...), $this->locations->findAll()));
        $io->writeln('Uploading obras…');
        $this->upsert('Obras', array_map(
            fn (Obra $obra): array => $this->obraFields($obra, $artistIds, $locationIds),
            $this->obras->findAll(),
        ));
        $forms = $this->provisionForms();

        $io->success(sprintf(
            'Grist now has %d artists, %d locations and %d obras. Forms: %s',
            count($artistIds), count($locationIds), count($this->obras->findAll()), implode(', ', $forms),
        ));
        $io->writeln(sprintf('%s/doc/%s', rtrim($this->host, '/'), $this->documentId));

        return Command::SUCCESS;
    }

    private function provisionTables(): void
    {
        $existing = array_column($this->grist->tables($this->documentId), 'id');
        foreach (self::TABLES as $tableId => $columns) {
            if (!in_array($tableId, $existing, true)) {
                $this->grist->request('POST', sprintf('docs/%s/tables', rawurlencode($this->documentId)), [
                    'json' => ['tables' => [['id' => $tableId, 'columns' => $columns]]],
                ]);
                continue;
            }

            // The table already exists, so creating it is a no-op -- but TABLES is the
            // source of truth for the schema and it does change (Photo, Status-as-Choice).
            // Without this, editing the constant silently does nothing to a live doc and
            // the two drift apart. Only ADD what is missing: never drop or retype a
            // column, since that would throw away data someone entered by hand.
            $present = array_column($this->grist->columns($this->documentId, $tableId), 'id');
            $missing = array_values(array_filter(
                $columns,
                static fn (array $column): bool => !in_array($column['id'], $present, true),
            ));

            if ([] !== $missing) {
                $this->grist->request('POST', sprintf('docs/%s/tables/%s/columns', rawurlencode($this->documentId), rawurlencode($tableId)), [
                    'json' => ['columns' => $missing],
                ]);
            }
        }
    }

    private function configureRelations(): void
    {
        $columns = array_column($this->grist->columns($this->documentId, 'Obras'), 'fields', 'id');
        $actions = [
            ['SetDisplayFormula', 'Obras', null, 'Artist', '$Artist.Name'],
            ['SetDisplayFormula', 'Obras', null, 'Location', '$Location.Name'],
        ];
        foreach (['Artist', 'Location'] as $columnId) {
            if (0 === ($columns[$columnId]['reverseCol'] ?? 0)) {
                $actions[] = ['AddReverseColumn', 'Obras', $columnId];
            }
        }
        $this->grist->request('POST', sprintf('docs/%s/apply', rawurlencode($this->documentId)), ['json' => $actions]);
    }

    /** @param list<array<string, mixed>> $rows
     *  @return array<string, int>
     */
    private function upsert(string $tableId, array $rows): array
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            try {
                $this->grist->upsertRecords($this->documentId, $tableId, array_map(
                    static fn (array $fields): array => ['require' => ['Code' => $fields['Code']], 'fields' => $fields],
                    $chunk,
                ));
            } catch (\Throwable $exception) {
                throw new \RuntimeException(sprintf(
                    'Unable to upsert %s rows %s through %s: %s',
                    $tableId, $chunk[0]['Code'], $chunk[array_key_last($chunk)]['Code'], $exception->getMessage(),
                ), previous: $exception);
            }
        }

        $ids = [];
        foreach ($this->grist->queryRecords($this->documentId, $tableId, limit: 10000) as $record) {
            $code = $record['fields']['Code'] ?? null;
            if (is_string($code)) {
                $ids[$code] = (int) $record['id'];
            }
        }

        return $ids;
    }

    /** @return list<string> */
    private function provisionForms(): array
    {
        $tableRefs = [];
        foreach ($this->grist->tables($this->documentId) as $table) {
            if (isset($table['id'], $table['fields']['tableRef']) && is_string($table['id'])) {
                $tableRefs[$table['id']] = (int) $table['fields']['tableRef'];
            }
        }
        $sections = $this->metadata('_grist_Views_section');
        $created = [];
        foreach (self::FORMS as $tableId => $form) {
            $name = $form['title'];
            $section = array_find($sections, static fn (array $record): bool =>
                'form' === ($record['fields']['parentKey'] ?? null)
                && $tableRefs[$tableId] === ($record['fields']['tableRef'] ?? null)
            );
            if (null === $section) {
                $this->grist->request('POST', sprintf('docs/%s/apply', rawurlencode($this->documentId)), [
                    'json' => [['CreateViewSection', $tableRefs[$tableId], 0, 'form', null, $name]],
                ]);
                $sections = $this->metadata('_grist_Views_section');
                $section = array_find($sections, static fn (array $record): bool =>
                    'form' === ($record['fields']['parentKey'] ?? null)
                    && $tableRefs[$tableId] === ($record['fields']['tableRef'] ?? null)
                );
            }

            if (null === $section) {
                throw new \RuntimeException(sprintf('Grist did not create the %s form section.', $tableId));
            }
            $sectionId = (int) $section['id'];
            $viewId = (int) $section['fields']['parentId'];
            $fieldRecords = array_values(array_filter(
                $this->metadata('_grist_Views_section_field'),
                static fn (array $record): bool => $sectionId === ($record['fields']['parentId'] ?? null),
            ));
            $columns = [];
            foreach ($this->grist->columns($this->documentId, $tableId) as $column) {
                if (isset($column['id'], $column['fields']['colRef']) && is_string($column['id'])) {
                    $columns[(int) $column['fields']['colRef']] = $column['id'];
                }
            }
            $columnRefs = array_flip($columns);
            $fieldsByColumn = [];
            foreach ($fieldRecords as $record) {
                $columnId = $columns[$record['fields']['colRef']] ?? null;
                if (is_string($columnId)) {
                    $fieldsByColumn[$columnId] = $record;
                }
            }
            $missingFields = [];
            foreach ($form['fields'] as $columnId) {
                if (!isset($fieldsByColumn[$columnId]) && isset($columnRefs[$columnId])) {
                    $missingFields[] = ['AddRecord', '_grist_Views_section_field', null, [
                        'parentId' => $sectionId,
                        'colRef' => $columnRefs[$columnId],
                    ]];
                }
            }
            if ([] !== $missingFields) {
                $this->grist->request('POST', sprintf('docs/%s/apply', rawurlencode($this->documentId)), ['json' => $missingFields]);
                $fieldRecords = array_values(array_filter(
                    $this->metadata('_grist_Views_section_field'),
                    static fn (array $record): bool => $sectionId === ($record['fields']['parentId'] ?? null),
                ));
                $fieldsByColumn = [];
                foreach ($fieldRecords as $record) {
                    $columnId = $columns[$record['fields']['colRef']] ?? null;
                    if (is_string($columnId)) {
                        $fieldsByColumn[$columnId] = $record;
                    }
                }
            }
            $orderedFields = [];
            foreach ($form['fields'] as $columnId) {
                if (isset($fieldsByColumn[$columnId])) {
                    $orderedFields[] = $fieldsByColumn[$columnId];
                }
            }
            $layout = ['type' => 'Layout', 'id' => 'layout-'.$sectionId, 'children' => [
                ['type' => 'Paragraph', 'id' => 'heading-'.$sectionId, 'text' => '# '.$name],
                ['type' => 'Paragraph', 'id' => 'intro-'.$sectionId, 'text' => $form['intro']],
                ['type' => 'Section', 'id' => 'fields-'.$sectionId, 'children' => array_map(
                    static fn (array $record): array => ['type' => 'Field', 'id' => 'field-'.$record['id'], 'leaf' => (int) $record['id']],
                    $orderedFields,
                )],
                ['type' => 'Submit', 'id' => 'submit-'.$sectionId, 'text' => 'Save '.$form['title']],
            ]];

            $this->grist->request('POST', sprintf('docs/%s/apply', rawurlencode($this->documentId)), ['json' => [
                ['UpdateRecord', '_grist_Views', $viewId, ['name' => $name]],
                ['UpdateRecord', '_grist_Views_section', $sectionId, [
                    'title' => $name,
                    'layoutSpec' => json_encode($layout, JSON_THROW_ON_ERROR),
                    'shareOptions' => json_encode(['publish' => true, 'form' => new \stdClass()], JSON_THROW_ON_ERROR),
                ]],
            ]]);
            $this->publishForm($viewId, strtolower($tableId).'-intake', $name);
            $created[] = $name;
        }

        return $created;
    }

    /** @return list<array{id:int, fields:array<string, mixed>}> */
    private function metadata(string $tableId): array
    {
        $result = $this->grist->request('GET', sprintf(
            'docs/%s/tables/%s/records?limit=500', rawurlencode($this->documentId), rawurlencode($tableId),
        ));

        return $result['records'] ?? [];
    }

    private function publishForm(int $viewId, string $linkId, string $label): void
    {
        $shares = $this->metadata('_grist_Shares');
        $share = array_find($shares, static fn (array $record): bool => $linkId === ($record['fields']['linkId'] ?? null));
        if (null === $share) {
            $this->grist->request('POST', sprintf('docs/%s/apply', rawurlencode($this->documentId)), ['json' => [[
                'AddRecord', '_grist_Shares', null, [
                    'linkId' => $linkId,
                    'options' => json_encode(['publish' => true], JSON_THROW_ON_ERROR),
                    'label' => $label,
                ],
            ]]]);
            $shares = $this->metadata('_grist_Shares');
            $share = array_find($shares, static fn (array $record): bool => $linkId === ($record['fields']['linkId'] ?? null));
        }
        if (null === $share) {
            throw new \RuntimeException(sprintf('Grist did not create the %s public share.', $label));
        }
        $page = array_find(
            $this->metadata('_grist_Pages'),
            static fn (array $record): bool => $viewId === ($record['fields']['viewRef'] ?? null),
        );
        if (null === $page) {
            throw new \RuntimeException(sprintf('Grist page for %s was not found.', $label));
        }
        $this->grist->request('POST', sprintf('docs/%s/apply', rawurlencode($this->documentId)), ['json' => [[
            'UpdateRecord', '_grist_Pages', (int) $page['id'], ['shareRef' => (int) $share['id']],
        ]]]);
    }

    /** @return array<string, mixed> */
    private function artistFields(Artist $artist): array
    {
        return ['Code' => $artist->code, 'Name' => $artist->name, 'BirthYear' => $artist->birthYear, 'Email' => $artist->email,
            'Phone' => $artist->phone, 'Social' => $artist->social, 'Bio' => $artist->bioBacking, 'Tagline' => $artist->sloganBacking,
            'DriveUrl' => $artist->driveUrl, 'YoutubeUrl' => $artist->youtubeUrl];
    }

    /** @return array<string, mixed> */
    private function locationFields(Location $location): array
    {
        return ['Code' => $location->code, 'Name' => $location->name, 'Status' => $location->status, 'Barrio' => $location->barrio,
            'Address' => $location->address, 'Type' => $location->type, 'ContactName' => $location->contactName,
            'Phone' => $location->phone, 'Latitude' => $location->lat, 'Longitude' => $location->lng];
    }

    /** @param array<string, int> $artistIds @param array<string, int> $locationIds
     *  @return array<string, mixed>
     */
    private function obraFields(Obra $obra, array $artistIds, array $locationIds): array
    {
        return ['Code' => $obra->code, 'Artist' => $artistIds[$obra->artist?->code ?? ''] ?? 0,
            'Location' => $locationIds[$obra->location?->code ?? ''] ?? 0, 'Exhibition' => $obra->exhibition,
            'Title' => $obra->title, 'Description' => $obra->description, 'Year' => $obra->year, 'Width' => $obra->width,
            'Height' => $obra->height, 'Depth' => $obra->depth, 'Materials' => $obra->materials, 'Price' => $obra->price,
            'Type' => $obra->type, 'Size' => $obra->size, 'DriveUrl' => $obra->driveUrl,
            'YoutubeUrl' => $obra->youtubeUrl, 'AudioCode' => $obra->audioCode];
    }
}
