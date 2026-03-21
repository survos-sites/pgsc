<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\MediaBundle\Entity\Audio;
use Survos\MediaBundle\Service\MediaRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\UnicodeString;

#[AsCommand('app:load', 'Import Artists, Locations and Obras from CSVs')]
class LoadCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ArtistRepository $artistRepo,
        private readonly LocationRepository $locationRepo,
        private readonly ObraRepository $obraRepo,
        private readonly MediaRegistry $mediaRegistry,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Path to data dir')] ?string $dir = null,
        #[Option('Purge all entities before import')] bool $reset = false,
    ): int {
        $dir ??= 'data';
        $io->title('Chijal CSV Import');

        if ($reset) {
            foreach ([Artist::class, Location::class, Obra::class] as $class) {
                $this->em->createQuery("DELETE FROM $class e")->execute();
            }
        }

        $artistsByCode = [];
        $locsByCode    = [];

        // ---- Artists
        $io->section('Importing Artists');
        foreach ($this->iterArtists("$dir/artistas.csv", "$dir/artists.csv") as $row) {
            $row = $this->normalizeRow($row);
            if (!$this->isActive($row['status'] ?? null)) {
                continue;
            }

            $email = $row['email'] ?? null;
            if (!$email) {
                $this->logger->warning('Skipping artist with no email', ['row' => $row]);
                continue;
            }

            $code = $this->normCode($row['code'] ?? null, $email, $row['name'] ?? null);
            if (!$code) {
                $this->logger->warning('Could not derive artist code', ['email' => $email]);
                continue;
            }

            $artist = $this->artistRepo->find($code) ?? new Artist($code);
            $this->em->persist($artist);

            $artist->name      = $row['name'] ?? $email;
            $artist->email     = $email;
            $artist->phone     = $row['whatsapp'] ?? null;
            $artist->birthYear = $this->parseBirthYear($row['birthyear'] ?? null);
            $artist->bio       = $row['bio'] ?? $row['long_bio'] ?? null;
            $artist->driveUrl  = $row['driveUrl'] ?? null;

            if ($row['tagline'] ?? null) {
                $artist->slogan = $row['tagline'];
            }

            if ($artist->driveUrl) {
                $this->ensureImage($artist->driveUrl, $artist);
            }

            $artist->youtubeUrl = $row['youtube_url'] ?? $row['youtubeurl'] ?? null;

            $artistsByCode[$code] = $artist;
        }
        $this->em->flush();

        // ---- Locations
        $io->section('Importing Locations');
        foreach ($this->csv("$dir/locations.csv")->getRecords() as $row) {
            $row = $this->normalizeRow($row);
            if (!$this->isActive($row['status'] ?? null, ['activo', 'active', 'sí', 'si'])) {
                continue;
            }

            $code = $this->normCode($row['code'] ?? null);
            if (!$code) {
                $this->logger->warning('Skipping location without code', ['row' => $row]);
                continue;
            }

            $loc = $this->locationRepo->find($code) ?? new Location($code);
            $this->em->persist($loc);

            $loc->name        = $row['name'] ?? $code;
            $loc->status      = $row['status'] ?? 'inactive';
            $loc->barrio      = $row['barrio'] ?? null;
            $loc->address     = $row['address'] ?? null;
            $loc->type        = $row['type'] ?? null;
            $loc->contactName = $row['contact'] ?? null;
            $loc->phone       = $row['phone'] ?? null;
            $loc->setGeoFromString($row['geo'] ?? null);

            $locsByCode[$code] = $loc;
        }
        $this->em->flush();

        // ---- Obras (omar_exhibition is the legacy CSV; march2026 comes from Google Sheets sync)
        $io->section('Importing Obras');
        foreach ($this->csv("$dir/omar_exhibition.csv")->getRecords() as $row) {
            $row = $this->normalizeRow($row);

            $code = $this->normCode($row['code'] ?? null);
            if (!$code) {
                continue;
            }

            $obra = $this->obraRepo->find($code) ?? new Obra($code);
            $this->em->persist($obra);

            $obra->title       = $row['title'] ?? null;
            $obra->description = $row['description'] ?? null;
            $obra->materials   = $row['material'] ?? null;
            $obra->size        = $row['size'] ?? null;
            $obra->year        = $this->parseInt($row['year'] ?? null);
            $obra->price       = $this->parseMoneyInt($row['price'] ?? null);
            $obra->type        = $row['type'] ?? null;

            $this->parseSizeIntoDims($obra, $obra->size);

            $artistCode = $this->normCode($row['artist_code'] ?? null);
            if ($artistCode && isset($artistsByCode[$artistCode])) {
                $obra->artist = $artistsByCode[$artistCode];
                $obra->artist->addObra($obra);
            } elseif ($artistCode) {
                $this->logger->warning('Unknown artist code on obra', ['obra' => $code, 'artist_code' => $artistCode]);
            }

            $locCode = $this->normCode($row['loc_code'] ?? null);
            if ($locCode && isset($locsByCode[$locCode])) {
                $obra->location = $locsByCode[$locCode];
                $obra->location->addObra($obra);
            } elseif ($locCode) {
                $this->logger->warning('Unknown location code on obra', ['obra' => $code, 'loc_code' => $locCode]);
            }

            // Images come from Flickr import (app:flickr) — skip Drive URLs here
            // to avoid duplicates when both CSV and Flickr are imported.

            if (!empty($row['audioUrl'])) {
                $audio = $this->mediaRegistry->ensureMedia($row['audioUrl'], Audio::class);
                $obra->audioCode = $audio->id;
            }

            $obra->youtubeUrl = $row['youtube_url'] ?? $row['youtubeUrl'] ?? null;
        }

        foreach ($locsByCode as $loc) {
            $loc->obraCount = $loc->obras->count();
        }
        foreach ($artistsByCode as $a) {
            $a->obraCount = $a->obras->count();
        }

        $this->em->flush();
        $io->success('Import complete.');
        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ensureImage(string $urls, Artist|Obra $entity): void
    {
        foreach (explode(',', $urls) as $url) {
            $url = trim($url);
            if (!$url) continue;
            $media = $this->mediaRegistry->ensureMedia($url);
            $entity->addImageCode($media->id);
        }
    }

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
            if (!empty($r['email'])) {
                $our[$r['email']] = $r;
            }
        }

        $merged = [];
        foreach ($this->csv($artists)->getRecords() as $r) {
            $r = $this->normalizeRow($r);
            if (empty($r['email'])) {
                continue;
            }
            $merged[$r['email']] = isset($our[$r['email']]) ? array_merge($our[$r['email']], $r) : $r;
        }
        foreach ($our as $email => $r) {
            if (!isset($merged[$email])) {
                $merged[$email] = $r;
            }
        }
        return array_values($merged);
    }

    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            if ($v === null) {
                $out[$k] = null;
                continue;
            }
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

    private function isActive(?string $status, array $activeWords = []): bool
    {
        return in_array($status, array_merge(['active', 'activo'], $activeWords), true);
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
        return preg_match('/\b(19|20)\d{2}\b/', $raw, $m) ? (int) $m[0] : null;
    }

    private function parseInt(?string $s): ?int
    {
        if ($s === null) return null;
        $s = preg_replace('/[^\d\-]/', '', $s);
        return $s === '' ? null : (int) $s;
    }

    private function parseMoneyInt(?string $s): ?int
    {
        if ($s === null) return null;
        $s = str_replace([',', '$', ' '], '', $s);
        return is_numeric($s) ? (int) $s : null;
    }

    private function parseSizeIntoDims(Obra $obra, ?string $size): void
    {
        if (!$size) return;
        if (preg_match('/(\d+)\s*[xX]\s*(\d+)(?:\s*[xX]\s*(\d+))?/u', $size, $m)) {
            $obra->width  = (int) $m[1];
            $obra->height = (int) $m[2];
            $obra->depth  = isset($m[3]) ? (int) $m[3] : null;
        }
    }
}
