<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Media;
use App\Entity\Obra;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\MediaRepository;
use App\Repository\ObraRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\CoreBundle\Service\SurvosUtils;
use Survos\GoogleSheetsBundle\Service\SheetService;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Syncs data from the Google Spreadsheet directly to the database.
 *
 * Tab routing (by name):
 *   Starts with @  → metadata
 *     @artists / @artistas  → Artist entities (keyed by code column)
 *     @locations / @ubicaciones → Location entities (keyed by code column)
 *   Everything else → Obra/exhibition sheet (tab name stored as obra.exhibition)
 *
 * The spreadsheet is the authority.  No CSV intermediary.
 */
class SyncService
{
    public const SAIS_ROOT = 'chijal';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ArtistRepository $artistRepo,
        private readonly LocationRepository $locationRepo,
        private readonly ObraRepository $obraRepo,
        private readonly MediaRepository $mediaRepo,
        private readonly SheetService $sheetService,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        #[Autowire('%env(GOOGLE_SPREADSHEET_ID)%')] private readonly ?string $spreadsheetId = null,
    ) {
    }

    public function getSpreadsheetId(): ?string
    {
        return $this->spreadsheetId;
    }

    /**
     * @return array{artists: int, locations: int, obras: int, skipped: string[], warnings: array<array{sheet:string, row:int|null, obra:string|null, message:string}>}
     */
    public function sync(bool $refresh = false): array
    {
        $counts   = ['artists' => 0, 'locations' => 0, 'obras' => 0, 'skipped' => [], 'warnings' => []];
        $warnings = &$counts['warnings'];

        // Obra sheets are deferred — artists/locations must be flushed first.
        $obraSheets = [];

        $this->sheetService->getData(
            $this->spreadsheetId,
            $refresh,
            function (string $sheetName, string $csvString) use (&$counts, &$obraSheets, &$warnings): void {

                if (str_starts_with($sheetName, '@')) {
                    $norm = mb_strtolower(trim($sheetName));
                    if (str_contains($norm, 'artist') || str_contains($norm, 'artista')) {
                        $counts['artists'] += $this->processArtistsSheet($csvString, $warnings);
                    } elseif (str_contains($norm, 'ubicacion') || str_contains($norm, 'location')) {
                        $counts['locations'] += $this->processLocationsSheet($csvString, $warnings);
                    } else {
                        $counts['skipped'][] = $sheetName;
                    }
                } else {
                    $obraSheets[$sheetName] = $csvString;
                }
            }
        );

        $this->em->flush();

        $artists   = [];
        $locations = [];
        foreach ($this->artistRepo->findAll() as $a) {
            $artists[$a->code] = $a;
        }
        foreach ($this->locationRepo->findAll() as $l) {
            $locations[$l->code] = $l;
        }

        foreach ($obraSheets as $sheetName => $csvString) {
            $counts['obras'] += $this->processObrasSheet($csvString, $sheetName, $artists, $locations, $warnings);
        }

        $this->em->flush();

        return $counts;
    }

    /**
     * Returns exhibition tab names — any tab that doesn't start with @.
     * Reads live from the spreadsheet so it works before obras are synced.
     * Falls back to DB distinct values if the spreadsheet is unreachable.
     *
     * @return string[]
     */
    public function getExhibitions(): array
    {
        try {
            $spreadsheet = $this->sheetService->getGoogleSpreadSheet($this->spreadsheetId);
            $names = [];
            foreach ($spreadsheet->getSheets() as $sheet) {
                $name = $sheet->getProperties()->getTitle();
                if (!str_starts_with($name, '@')) {
                    $names[] = $name;
                }
            }
            return $names;
        } catch (\Throwable) {
            // Fall back to what's already in the DB
            $result = $this->em
                ->createQuery('SELECT DISTINCT o.exhibition FROM ' . Obra::class . ' o WHERE o.exhibition IS NOT NULL ORDER BY o.exhibition')
                ->getScalarResult();
            return array_column($result, 'exhibition');
        }
    }

    // -------------------------------------------------------------------------
    // Sheet processors
    // -------------------------------------------------------------------------

    /**
     * @artists / @artistas tab.
     *
     * Expected columns (already normalised/English — this is the admin sheet,
     * not the Google Form response tab):
     *   code, email, name, phone, youtubeUrl, audioUrl, bio, birthYear, social, status
     *
     * The Google Form response tab (@DATOS ARTISTAS) has long Spanish headers
     * and no code column — it is handled by the same processor via the column
     * map that normalises headers before any field access.
     */
    private function processArtistsSheet(string $csvString, array &$warnings): int
    {
        $reader = Reader::createFromString($csvString);
        $reader->setHeaderOffset(0);

        $count = 0;
        foreach ($reader->getRecords() as $raw) {
            $row = $this->normalizeHeaders($raw);

            $email = $row['email'] ?? null;
            $code  = $row['code'] ?? null;

            if (!$code && !$email) {
                continue;
            }

            $code = $this->normCode($code, $email, $row['name'] ?? null);
            if (!$code) {
                $warnings[] = ['sheet' => '@artists', 'row' => null, 'obra' => null,
                    'message' => "Cannot derive code for artist with email '$email'"];
                continue;
            }

            // Upsert: prefer finding by email, then by code
            $artist = ($email ? $this->artistRepo->findOneBy(['email' => $email]) : null)
                ?? $this->artistRepo->find($code)
                ?? new Artist($code);

            if (!$this->em->contains($artist)) {
                $this->em->persist($artist);
            }

            if ($email)              $artist->email     = $email;
            if ($row['name'] ?? null) $artist->name     = $row['name'];
            if ($row['phone'] ?? null) $artist->phone   = $row['phone'];
            if ($row['social'] ?? null) $artist->social = $row['social'];

            $artist->birthYear = $this->parseBirthYear($row['birthyear'] ?? $row['birth_year'] ?? null);

            $bio = $row['long_bio'] ?? $row['bio'] ?? $row['short_bio'] ?? null;
            if ($bio) $artist->bio = $bio;

            $tagline = $row['tagline'] ?? $row['slogan'] ?? null;
            if ($tagline) $artist->slogan = $tagline;

            $youtube = $row['youtubeurl'] ?? $row['youtube_url'] ?? null;
            if ($youtube) $artist->youtubeUrl = $youtube;

            $drive = $row['driveurl'] ?? $row['drive_url'] ?? null;
            if ($drive) {
                $artist->driveUrl = $drive;
                $this->addToMedia($drive, $artist);
            }

            $count++;
        }

        $this->em->flush();
        return $count;
    }

    /**
     * @locations / @ubicaciones tab.
     *
     * Expected columns: code, status, barrio, name, address, type, contact, phone, geo
     */
    private function processLocationsSheet(string $csvString, array &$warnings): int
    {
        $reader = Reader::createFromString($csvString);
        $reader->setHeaderOffset(0);

        $count = 0;
        foreach ($reader->getRecords() as $raw) {
            $row  = $this->normalizeHeaders($raw);
            $code = $row['code'] ?? null;
            if (!$code) {
                $code = $this->normCode(null, $row['email'] ?? null, $row['name'] ?? $row['nombre'] ?? null);
            }
            if (!$code) {
                continue;
            }
            $code = $this->normCode($code);

            $loc = $this->locationRepo->find($code) ?? new Location($code);
            if (!$this->em->contains($loc)) {
                $this->em->persist($loc);
            }

            $loc->name        = $row['name'] ?? $row['nombre'] ?? $code;
            $loc->status      = $row['status'] ?? 'active';
            $loc->barrio      = $row['barrio'] ?? null;
            $loc->address     = $row['address'] ?? $row['direccion'] ?? null;
            $loc->type        = $row['type'] ?? $row['tipo'] ?? null;
            $loc->contactName = $row['contact'] ?? null;
            $loc->phone       = $row['phone'] ?? $row['telefono'] ?? null;

            if ($geo = $row['geo'] ?? null) {
                $loc->setGeoFromString($geo);
            }

            $count++;
        }

        $this->em->flush();
        return $count;
    }

    /**
     * Exhibition / obra sheet — any tab NOT starting with @.
     *
     * Expected columns: code, artist_code, loc_code, title, material, size,
     *                   year, price, description, photoUrl, audioUrl, youtubeUrl
     *
     * @param array<string, Artist>   $artists   pre-loaded by code
     * @param array<string, Location> $locations pre-loaded by code
     */
    private function processObrasSheet(
        string $csvString,
        string $sheetName,
        array $artists,
        array $locations,
        array &$warnings,
    ): int {
        $reader = Reader::createFromString($csvString);
        $reader->setHeaderOffset(0);

        $count  = 0;
        $rowNum = 1;
        foreach ($reader->getRecords() as $raw) {
            $rowNum++;
            $row  = $this->normalizeHeaders($raw);
            $code = $row['code'] ?? null;
            if (!$code) {
                continue;
            }

            SurvosUtils::assertKeyExists('artist_code', $row,
                "Sheet '$sheetName' row $rowNum (obra=$code) has no artist_code column at all"
            );
            $artistCode = $row['artist_code'];

            if (!$artistCode) {
                $warnings[] = ['sheet' => $sheetName, 'row' => $rowNum, 'obra' => $code,
                    'message' => "No artist_code value — fill in the artist_code cell"];
                continue;
            }

            if (!array_key_exists($artistCode, $artists)) {
                $warnings[] = ['sheet' => $sheetName, 'row' => $rowNum, 'obra' => $code,
                    'message' => "Artist code '$artistCode' not found — add code='$artistCode' to the @artists tab"];
                continue;
            }

            $obra = $this->obraRepo->find($code) ?? new Obra($code);
            if (!$this->em->contains($obra)) {
                $this->em->persist($obra);
            }

            $obra->artist     = $artists[$artistCode];
            $obra->exhibition = $sheetName;
            $obra->title      = $row['title'] ?? null;
            $obra->description= $row['description'] ?? null;
            $obra->materials  = $row['material'] ?? null;
            $obra->size       = $row['size'] ?? null;
            $obra->year       = $this->parseInt($row['year'] ?? null);
            $obra->price      = $this->parseMoneyInt($row['price'] ?? null);

            $locCode = $row['loc_code'] ?? null;
            if ($locCode && !array_key_exists($locCode, $locations)) {
                $warnings[] = ['sheet' => $sheetName, 'row' => $rowNum, 'obra' => $code,
                    'message' => "Location code '$locCode' not found — add it to the @locations tab"];
            } elseif ($locCode) {
                $obra->location = $locations[$locCode];
            }

            $count++;
        }

        $this->em->flush();
        return $count;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Normalise all header keys in a row to lowercase+underscored ASCII,
     * with specific mappings for the long Spanish Google Form headers.
     */
    private function normalizeHeaders(array $row): array
    {
        static $headerMap = [
            // Spanish Google Form headers → internal key
            'nombre completo y/o artístico'   => 'name',
            'año de nacimiento'               => 'birthyear',
            'pronombres preferidos'           => 'gender',
            'redes sociales'                  => 'social',
            'teléfono'                        => 'phone',
            'método de contacto preferido'    => 'contact_method',
            'biografía larga'                 => 'long_bio',
            'biografía larga (2-3 párrafos)'  => 'long_bio',
            'short_bio'                       => 'short_bio',
            'shortbio'                        => 'short_bio',
            '¿tu estudio esta abierto'        => 'studio_open',
            'si tu estudio esta abierto'      => 'studio_address',
            'te pedimos una foto de los hombros' => 'driveurl',
            'te pedimos una foto de tu local' => 'driveurl',
            'aparte de español'               => 'languages',
            'incluye todos los tipos de arte' => 'art_types',
            'un slogan'                       => 'tagline',
            'email address'                   => 'email',
            // Location form headers
            'nombre del negocio'              => 'name',
            '¿qué tipo de negocio tienes?'    => 'type',
            'incluye los enlaces de internet' => 'social',
            'proporciona un enlace de google map' => 'maps_url',
            'timestamp'                       => 'timestamp',
        ];

        $out = [];
        foreach ($row as $rawKey => $rawValue) {
            // Trim whitespace from value
            $value = is_string($rawValue)
                ? (trim(trim($rawValue), "\"' \t\n\r\0\x0B") ?: null)
                : $rawValue;

            // Normalise key: lowercase, collapse spaces
            $key = mb_strtolower(trim((string) $rawKey));
            $key = preg_replace('/\s+/', ' ', $key);

            // Exact match in map
            if (isset($headerMap[$key])) {
                $out[$headerMap[$key]] ??= $value;
                continue;
            }

            // Prefix match for long Spanish questions
            foreach ($headerMap as $pattern => $target) {
                if (str_starts_with($key, $pattern)) {
                    $out[$target] ??= $value;
                    continue 2;
                }
            }

            // Fall back: convert to snake_case (remove accents, replace spaces)
            $snake = strtr($key, [
                ' '=>'_', '-'=>'_', '/'=>'_',
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
                '¿'=>'', '?'=>'', '¡'=>'', '!'=>'', ','=>'', '.'=>'',
                '('=>'', ')'=>'',
            ]);
            $out[$snake] ??= $value;
        }

        return $out;
    }

    private function normCode(?string $code, ?string $email = null, ?string $name = null): ?string
    {
        if ($code) {
            $c = preg_replace('/\s+/', '', mb_strtolower(trim($code)));
            if ($c !== '') return $c;
        }
        if ($email) {
            return mb_strtolower((new UnicodeString($email))->before('@')->toString());
        }
        if ($name) {
            $ascii   = (new UnicodeString($name))->ascii()->toString();
            $parts   = array_values(array_filter(explode(' ', mb_strtolower($ascii))));
            $letters = array_map(static fn($p) => preg_replace('/(?<=\w).*/', '', $p), $parts);
            $c = implode('', $letters);
            return $c !== '' ? $c : null;
        }
        return null;
    }

    private function parseBirthYear(?string $raw): ?int
    {
        if (!$raw) return null;
        return preg_match('/\b(19|20)\d{2}\b/', $raw, $m) ? (int)$m[0] : null;
    }

    private function parseInt(?string $s): ?int
    {
        if ($s === null) return null;
        $s = preg_replace('/[^\d\-]/', '', $s);
        return $s === '' ? null : (int)$s;
    }

    private function parseMoneyInt(?string $s): ?int
    {
        if ($s === null) return null;
        $s = str_replace([',', '$', ' '], '', $s);
        return is_numeric($s) ? (int)$s : null;
    }

    private function upsertMedia(string $code, string $original, string $type): void
    {
        $media = $this->mediaRepo->findOneBy(['code' => $code]) ?? new Media($code);
        $this->em->persist($media);
        $media->type        = $type;
        $media->originalUrl = $original;

        if (count($this->validator->validate($media)) === 0) {
            $this->em->flush();
        }
    }

    private function addToMedia(?string $driveUrl, Artist $artist): void
    {
        if (!$driveUrl) return;
        foreach (explode(',', $driveUrl) as $url) {
            $url = trim($url);
            if (!$url) continue;
            $imgCode = SaisClientService::calculateCode($url, self::SAIS_ROOT);
            $artist->addImageCode($imgCode);
            $this->upsertMedia($imgCode, $url, 'image');
        }
    }
}
