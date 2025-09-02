<?php
declare(strict_types=1);

namespace App\Command;

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
use Survos\SaisBundle\Model\AccountSetup;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\String\UnicodeString;

#[AsCommand('app:load', 'Import Artists, Locations and Obras from CSVs')]
class LoadCommand extends Command
{
    public const SAIS_ROOT = 'chijal';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ArtistRepository $artistRepo,
        private readonly LocationRepository $locationRepo,
        private readonly ObraRepository $obraRepo,
        private readonly MediaRepository $mediaRepo,
        private readonly SaisClientService $sais,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
    ) { parent::__construct(); }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Path to data dir containing artistas.csv, artists.csv, locations.csv, piezas.csv')] ?string $dir = null,
        #[Option('Refresh cached Google Sheets first (placeholder)')] ?bool $refresh = null,
        #[Option('purge first')] ?bool $reset = null,
        #[Option('Also initialize SAIS account (images/audio)')] ?bool $sais = null
    ): int {
        $dir ??= 'data';
        $io->title('Chijal CSV Import');

        if ($reset) {
            foreach ([Artist::class, Location::class, Obra::class, Media::class] as $class) {
                $this->em->createQuery("DELETE FROM $class e")->execute();
            }
        }

        if ($sais) {
            $response = $this->sais->accountSetup(new AccountSetup(self::SAIS_ROOT, 100));
            $io->info(sprintf("Sais %s setup with %d bin(s)", $response['code'], $response['binCount']));
        }

        $artistsByCode = [];
        $locsByCode = [];

        // ---- Artists
        $io->section('Importing Artists');
        foreach ($this->iterArtists("$dir/artistas.csv", "$dir/artists.csv") as $row) {
            $row = $this->normalizeRow($row);
            if (!$this->isActive($row['status']??null)) {
                continue;
            }


            $email = $row['email'] ?? null;
            if (!$email) { $this->logger->warning('Skipping artist with no email', ['row'=>$row]); continue; }

            $code = $this->normCode($row['code'] ?? null, $email, $row['name'] ?? null);
            if (!$code) { $this->logger->warning('Could not derive artist code', ['email'=>$email,'row'=>$row]); continue; }

            $artist = $this->artistRepo->find($code) ?? new Artist($code);
            $this->em->persist($artist);

            $artist->name = $row['name'] ?? $email;
            $artist->email = $email;
            $artist->phone = $row['whatsapp'] ?? null;
            $artist->birthYear = $this->parseBirthYear($row['birthyear'] ?? ($row['birthyear'] ?? null));

            $artist->driveUrl = $row['driveUrl']??null;

            if ($row['tagline']??null) {
                $artist->slogan = $row['tagline'];
            }


            $artist->bio = $row['bio'] ?? ($row['long_bio'] ?? null);
//            SurvosUtils::assertKeyExists('tagline', $row);
//            $artist->slogan = $row['tagline'];

            if ($artist->driveUrl) {
//                $this->addToMedia($artist->driveUrl, $artist);
            }

            if (!empty($row['youtube_url'])) {
                $artist->youtubeUrl = $row['youtube_url'];
            } elseif (!empty($row['youtubeurl'])) {
                $artist->youtubeUrl = $row['youtubeurl'];
            }



            $artistsByCode[$code] = $artist;
        }
//        dd(array_keys($artistsByCode));
        $this->em->flush();

        // ---- Locations
        $io->section('Importing Locations');
        foreach ($this->csv("$dir/locations.csv")->getRecords() as $row) {
            $row = $this->normalizeRow($row);
            if (!$this->isActive(($row['status'] ?? null), ['activo','active','sí','si'])) { continue; }

            $code = $this->normCode($row['code'] ?? null);
            if (!$code) { $this->logger->warning('Skipping location without code', ['row'=>$row]); continue; }

            $loc = $this->locationRepo->find($code) ?? new Location($code);
            $this->em->persist($loc);

            $loc->name = $row['name'] ?? $code;
            $loc->status = $row['status'] ?? 'inactive';
            $loc->barrio = $row['barrio'] ?? null;
            $loc->address = $row['address'] ?? null;
            $loc->type = $row['type'] ?? null;
            $loc->contactName = $row['contact'] ?? null;
            $loc->phone = $row['phone'] ?? null;
            $loc->setGeoFromString($row['geo'] ?? null);
//            dd($row['geo'], $loc->lng, $loc->lat);

            $locsByCode[$code] = $loc;
        }
        $this->em->flush();

        // ---- Obras
        $io->section('Importing Obras');
        foreach ($this->csv("$dir/piezas.csv")->getRecords() as $row) {
            $row = $this->normalizeRow($row);

            $code = $this->normCode($row['code'] ?? null);
            if (!$code) { continue; }

            $obra = $this->obraRepo->find($code) ?? new Obra($code);
            $this->em->persist($obra);

            $obra->title = $row['title'] ?? null;
            $obra->description = $row['description'] ?? null;
            $obra->materials = $row['material'] ?? null;
            $obra->size = $row['size'] ?? null;
            $obra->year = $this->parseInt($row['year'] ?? null);
            $obra->price = $this->parseMoneyInt($row['price'] ?? null);
            $obra->type = $row['type'] ?? null;

            $this->parseSizeIntoDims($obra, $obra->size);

            $artistCode = $this->normCode($row['artist_code'] ?? null);
            if ($artistCode && isset($artistsByCode[$artistCode])) {
                $obra->artist = $artistsByCode[$artistCode];
                $obra->artist->addObra($obra);
            } elseif ($artistCode) {
                $this->logger->warning('Unknown artist code on obra', ['obra'=>$code, 'artist_code'=>$artistCode]);
            }

            $locCode = $this->normCode($row['loc_code'] ?? null);
            if ($locCode && isset($locsByCode[$locCode])) {
                $obra->location = $locsByCode[$locCode];
                $obra->location->addObra($obra);
            } elseif ($locCode) {
                $this->logger->warning('Unknown location code on obra', ['obra'=>$code, 'loc_code'=>$locCode]);
            }

            if (0) // all in flickr now
            foreach (['photo_url','photodriveurl2','photoUrl','photoDriveUrl2'] as $pf) {
                if (!empty($row[$pf])) {
                    $drive = $row[$pf];
                    $imgCode = SaisClientService::calculateCode($drive, self::SAIS_ROOT);
                    $obra->addImageCode($imgCode);
                    $this->upsertMedia($imgCode, original: $drive, type: 'image');
                    $obra->driveUrl ??= $drive; // first becomes primary
                }
            }

            if (!empty($row['audioUrl'])) {
                $a = $row['audioUrl'];
                $aCode = SaisClientService::calculateCode($a, self::SAIS_ROOT);
                $obra->audioCode = $aCode;
                $this->upsertMedia($aCode, original: $a, type: 'audio');
            }

            if (!empty($row['youtube_url'])) {
                $obra->youtubeUrl = $row['youtube_url'];
            } elseif (!empty($row['youtubeUrl'])) {
                $obra->youtubeUrl = $row['youtubeUrl'];
            }
        }

        foreach ($locsByCode as $loc) { $loc->obraCount = $loc->obras->count(); }
        foreach ($artistsByCode as $a) { $a->obraCount = $a->obras->count(); }

        $this->em->flush();
        $io->success('Import complete.');
        return Command::SUCCESS;
    }

    // -------------- helpers --------------

    private function csv(string $path): Reader
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('CSV file not found: %s', $path));
        }
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        return $csv;
    }

    private function iterArtists(string $artistas, string $artists): iterable
    {
        $our = [];
        foreach ($this->csv($artistas)->getRecords() as $r) {
            $r = $this->normalizeRow($r);
            if (!empty($r['email'])) { $our[$r['email']] = $r; }
        }

        $merged = [];
        foreach ($this->csv($artists)->getRecords() as $r) {
            $r = $this->normalizeRow($r);
            if (empty($r['email'])) { continue; }
            $merged[$r['email']] = isset($our[$r['email']]) ? array_merge($our[$r['email']], $r) : $r;
        }
        foreach ($our as $email => $r) {
            if (!isset($merged[$email])) { $merged[$email] = $r; }
        }
        return array_values($merged);
    }

    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
//            $k = $this->normKey($k);
            if ($v === null) { $out[$k] = null; continue; }
            if (is_string($v)) {
                $v = trim(preg_replace('/\s+/', ' ', $v));
                $v = trim($v, "\"' \t\n\r\0\x0B");
                $out[$k] = ($v === '') ? null : $v;
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private function normKey(?string $key): ?string
    {
        if ($key === null) { return null; }
        $key = mb_strtolower(trim($key));
        $repl = [' '=>'_','-'=>'_','/'=>'_','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','.'=>'_'];
        return strtr($key, $repl);
    }

    private function isActive(?string $status, array $activeWords=[]): bool
    {
        return in_array($status, ['active', 'activo']);
//        || in_array($status, $activeWords, true);
//        if (!$status) { return false; }
//        $s = mb_strtolower(trim($status));
//
//        $s = str_replace(['no active','not active','inactivo','inactive'], 'inactive', $s);
//        if ($s === 'acive') { $s = 'active'; }
//        foreach ($activeWords as $w) { if ($s === $w) { return true; } }
//        return $s === 'active';
    }

    private function normCode(?string $code, ?string $email = null, ?string $name = null): ?string
    {
        if ($code) {
            $c = preg_replace('/\s+/', '', mb_strtolower(trim($code)));
            if ($c !== '') { return $c; }
        }
        if ($email) {
            $u = new UnicodeString($email);
            return mb_strtolower($u->before('@')->toString());
        }
        if ($name) {
            $n = new UnicodeString($name);
            $ascii = $n->ascii()->toString();
            $parts = array_values(array_filter(explode(' ', mb_strtolower($ascii))));
            $letters = array_map(static fn($p) => preg_replace('/(?<=\w).*/', '', $p), $parts);
            $c = implode('', $letters);
            return $c !== '' ? $c : null;
        }
        return null;
    }

    private function parseBirthYear(?string $raw): ?int
    {
        if (!$raw) { return null; }
        return preg_match('/\b(19|20)\d{2}\b/', $raw, $m) ? (int)$m[0] : null;
    }

    private function parseInt(?string $s): ?int
    {
        if ($s === null) { return null; }
        $s = preg_replace('/[^\d\-]/', '', $s);
        return $s === '' ? null : (int)$s;
    }

    private function parseMoneyInt(?string $s): ?int
    {
        if ($s === null) { return null; }
        $s = str_replace([',','$',' '], '', $s);
        return is_numeric($s) ? (int)$s : null;
    }

    private function parseSizeIntoDims(Obra $obra, ?string $size): void
    {
        if (!$size) { return; }
        if (preg_match('/(\d+)\s*[xX]\s*(\d+)(?:\s*[xX]\s*(\d+))?/u', $size, $m)) {
            $obra->width = (int)$m[1];
            $obra->height = (int)$m[2];
            $obra->depth = isset($m[3]) ? (int)$m[3] : null;
        }
    }

    private function upsertMedia(string $code, string $original, string $type): ?Media
    {
        return null;
        $media = $this->mediaRepo->findOneBy(['code' => $code]) ?? new Media($code);
        $this->em->persist($media);
        $media->type = $type;
        $media->originalUrl = $original;
        $errors = $this->validator->validate($media);
        if (count($errors) > 0) {
            dd($errors);
        }
        $this->em->flush(); // hack, not tracking duplidates right
        return $media;
    }

    // @todo: move this into a service so that FlickrListener can access it.
    private function addToMedia(?string $driveUrl, Artist|Obra $entity)
    {
        foreach (explode(',', $driveUrl) as $url) {
            $url = trim($url);
            $imgCode = SaisClientService::calculateCode($url, self::SAIS_ROOT);
            $entity->addImageCode($imgCode);
            $media = $this->upsertMedia($imgCode, original: $url, type: 'image');
        }

    }
}
