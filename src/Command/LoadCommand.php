<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Media;
use App\Entity\Location;
use App\Entity\Obra;
use App\Repository\ArtistRepository;
use App\Repository\MediaRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\CoreBundle\Service\SurvosUtils;
use Survos\SaisBundle\Model\AccountSetup;
use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\String\u;

#[AsCommand('app:load', 'Load the chijal data')]
class LoadCommand extends Command
{
    public const SAIS_ROOT = 'chijal';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ObjectMapperInterface  $objectMapper,
        private readonly ArtistRepository       $artistRepo,
        private readonly LocationRepository     $locationRepo,
        private readonly SaisClientService      $sais,
        private readonly ObraRepository         $obraRepo,
        private readonly MediaRepository        $imageRepo,
        private readonly ValidatorInterface     $validator,
        private readonly TranslatorInterface    $translator,
        private readonly UrlGeneratorInterface  $urls,
        private readonly LoggerInterface        $logger,
        private readonly MediaRepository $mediaRepository,
    ) { parent::__construct(); }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Refresh the cached data from Google Sheets')] ?bool $refresh = null,
        #[Option('Dispatch SAIS requests')] ?bool $resize = null
    ): int {
        if ($refresh) {
            $io->writeln('Option refresh: true');
        }

        if ($resize) {
            $response = $this->sais->accountSetup(new AccountSetup(self::SAIS_ROOT, 100));
        }

        $artists   = []; // code => Artist
        $locations = []; // code => Location

        // ---------- Artists ----------
        foreach ($this->iterArtists() as $row) {
            // normalize once
            $row = $this->normalizeRow($row);

            if (!$this->isActive($row['status'] ?? null)) {
                continue;
            }

            $email = $row['email'] ?? null;
            if (!$email) {
                $this->logger->warning('Skipping artist without email', ['row' => $row]);
                continue;
            }

            // code may be missing in artistas.csv; derive if needed
            $code = $this->normCode($row['code'] ?? null, $email, $row['name'] ?? null);

            if(!$artist = $this->artistRepo->find($code)) {
                $artist = new Artist($code);
                $this->em->persist($artist);
            }
            
            $artist
                ->setEmail($email)
                ->setCode($code)
                ->setName($row['name'] ?? $email)
                ->setSlogan($this->truncate($row['tagline'] ?? '', 80))
                ->setDriveUrl($row['driveurl'] ?? null)
                ->setBio($row['long_bio'] ?? ($row['bio'] ?? ''), 'es')
                ->setBirthYear($this->parseBirthYear($row['nacimiento'] ?? ($row['birthyear'] ?? null)));

            $artist->imageCodes=[];
            // Create Media entity directly in database (commenting out SAIS dispatch for now)
            if ($driveUrl = $artist->getDriveUrl()) {
                    // Calculate SAIS code for the image
                    $saisImageCode = SaisClientService::calculateCode($driveUrl, self::SAIS_ROOT);
                    $artist->imageCodes[] = $saisImageCode;

                    // Create or update Media entity directly in database

                    if (!$image = $this->imageRepo->findByCode($saisImageCode)) {
                        $image = new Media($saisImageCode);
                        $this->em->persist($image);
                    }
                    $image->setOriginalUrl($driveUrl);

                    // Store the SAIS image code for this artist

                    $this->logger->info('Media entity created for artist', [
                        'email' => $email,
                        'saisImageCode' => $saisImageCode,
                        'driveUrl' => $artist->getDriveUrl()
                    ]);
            }

            $artist->mergeNewTranslations();
            $this->validateOrFail($artist, $io);

            $artists[$artist->getCode()] = $artist;
        }

        // ---------- Locations ----------
        foreach ($this->iterLocations() as $rowRaw) {
            $row = $this->normalizeRow($rowRaw);

            if (!$this->isActive($row['status'] ?? null, activeWords: ['activo', 'active', 'sí', 'si'])) {
                continue;
            }

            $code = $this->normCode($row['code'] ?? null);

            if (!$code) {
                $this->logger->warning('Skipping location with empty code', ['row' => $row]);
                continue;
            }

            if (
                !$location = $this->locationRepo->find($code)
                ?? $this->locationRepo->findOneBy(['name' => $row['nombre'] ?? null])
            ) {
                $location = new Location($code);
                $this->em->persist($location);
            }

            $location
                ->setCode($code)
                ->setName($row['nombre'] ?? $code)
                ->setStatus($row['status'] ?? 'activo')
                ->setAddress($row['direcciones'] ?? null);

            $locations[$location->getCode()] = $location;
        }

        $this->em->flush();

        // ---------- Obras (pieces) ----------
        $piezas = $this->csv('data/piezas.csv');
        $piezas->setHeaderOffset(0);

        foreach ($piezas->getRecords() as $row) {
            $row = $this->normalizeRow($row);

            $code = $this->normCode($row['code'] ?? null);
            if (!$code) {
                continue;
            }

            if (!$obra = $this->obraRepo->find($code)) {
                $obra = new Obra($code);
                $this->em->persist($obra);
            }

            // Optional audio process
            if ($audioUrl = $row['audiodriveurl']) {
                    $saisCode = SaisClientService::calculateCode($audioUrl, self::SAIS_ROOT);
                    if (!$media = $this->mediaRepository->findOneBy(['code' => $saisCode])) {
                        $media = new Media($saisCode);
                    }
                    $media->type = 'audio';
                    $media->originalUrl = $audioUrl;
                    if ($resize) {
                    }
            }

            // Basic fields
            $obra
                ->setMaterials($row['material'] ?? null)
                ->setYoutubeUrl($row['youtubeurl'] ?? null)
                ->setDriveUrl($row['photodriveurl'] ?? null)
                ->setTitle($row['title'] ?? null)
                ->setSize($row['size'] ?? null);

            // Location link
            if (!empty($row['loc_code'])) {
                $locCode = $this->normCode($row['loc_code']);
                if (isset($locations[$locCode])) {
                    $locations[$locCode]->addObra($obra);
                } else {
                    $this->logger->warning('Unknown location code on pieza', ['pieza' => $code, 'loc_code' => $locCode]);
                }
            }

            // Artist link
            if (!empty($row['artist_code'])) {
                $artistCode = $this->normCode($row['artist_code']);
                if (isset($artists[$artistCode])) {
                    $artists[$artistCode]->addObra($obra);
                } else {
                    $this->logger->warning('Unknown artist code on pieza', ['pieza' => $code, 'artist_code' => $artistCode]);
                }
            }

            // Create Media entity directly in database (commenting out SAIS dispatch for now)
            if ($obra->getDriveUrl()) {
                    // Calculate SAIS code for the image
                    $saisImageCode = SaisClientService::calculateCode($obra->getDriveUrl(), self::SAIS_ROOT);

                    // Create or update Media entity directly in database
                    $image = $this->imageRepo->findByCode($saisImageCode);
                    if (!$image) {
                        $image = new Media($saisImageCode);
                        $this->em->persist($image);
                    }
                    $image->type = 'image';
                    $image->setOriginalUrl($obra->getDriveUrl());

                    // Store the SAIS image code for this obra
                    $obra->addImageCode($saisImageCode);

                    $this->logger->info('Media entity created for obra', [
                        'code' => $code,
                        'saisImageCode' => $saisImageCode,
                        'driveUrl' => $obra->getDriveUrl()
                    ]);
            }
        }

        // ---------- counts ----------
        foreach ($this->locationRepo->findAll() as $loc) {
            $loc->setObraCount($loc->getObras()->count());
        }
        foreach ($this->artistRepo->findAll() as $art) {
            $art->setObraCount($art->getObras()->count());
        }

        $this->em->flush();
        $io->success(self::class . ' success.');

        return Command::SUCCESS;
    }

    // ================== Helpers ==================

    private function iterArtists(): iterable
    {
        // Internal list (codes + status) keyed by email
        $our = [];
        foreach ($this->csv('data/artistas.csv', header: 0)->getRecords() as $r) {
            $r = $this->normalizeRow($r);
            if (!empty($r['email'])) {
                $our[$r['email']] = $r;
            }
        }

        // Google Form responses (details) keyed by email
        $responses = [];
        foreach ($this->csv('data/artists.csv', header: 0)->getRecords() as $r) {
            $r = $this->normalizeRow($r);
            if (empty($r['email'])) {
                continue;
            }
            if (!isset($our[$r['email']])) {
                // Warn but still include, with minimal defaults
                $this->logger->warning('Email present in artists.csv but missing in artistas.csv', ['email' => $r['email']]);
                $merged = $r;
            } else {
                $merged = array_merge($our[$r['email']], $r);
            }
            $responses[$r['email']] = $merged;
        }

        // Also include any internal-only rows that didn’t fill the form (if desired)
        foreach ($our as $email => $r) {
            if (!isset($responses[$email])) {
                $responses[$email] = $r;
            }
        }

        return array_values($responses);
    }

    private function iterLocations(): iterable
    {
        return $this->csv('data/locations.csv', header: 0)->getRecords();
    }

    private function csv(string $path, int $header = 0): Reader
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset($header);
        return $csv;
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $k => $v) {
            if ($v === null) { $normalized[$this->normKey($k)] = null; continue; }
            $v = is_string($v) ? trim($v) : $v;
            // Collapse weird multiple spaces and stray quotes
            if (is_string($v)) {
                $v = preg_replace('/\s+/', ' ', $v);
                $v = trim($v, "\"' \t\n\r\0\x0B");
            }
            $normalized[$this->normKey($k)] = $v === '' ? null : $v;
        }
        return $normalized;
    }

    private function normKey(?string $key): ?string
    {
        if ($key === null) { return null; }
        $key = strtolower(trim($key));
        $key = str_replace(
            [' ', '-', '/', 'á','é','í','ó','ú','ñ'],
            ['_', '_', '_','a','e','i','o','u','n'],
            $key
        );
        return $key;
    }

    private function isActive(?string $status, array $activeWords = ['active', 'activo', 'yes', 'sí', 'si']): bool
    {
        if (!$status) { return false; }
        $s = strtolower(trim($status));
        // common typos & variants
        $s = str_replace(['no active', 'not active', 'inactivo', 'inactive'], 'inactive', $s);
        if ($s === 'acive') { $s = 'active'; }

        foreach ($activeWords as $word) {
            if ($s === $word) { return true; }
        }
        return $s === 'active';
    }

    private function normCode(?string $code, ?string $email = null, ?string $name = null): ?string
    {
        $code = $code ? strtolower(trim($code)) : null;
        if ($code) {
            $code = preg_replace('/\s+/', '', $code);
        }
        if (!$code && $email) {
            $code = strtolower(u($email)->before('@')->toString());
        }
        if (!$code && $name) {
            $code = $this->initials($name);
        }
        return $code ?: null;
    }

    private function initials(string $name): string
    {
        $name = u($name)->ascii()->toString();
        $parts = array_values(array_filter(explode(' ', strtolower($name))));
        $letters = array_map(fn($n) => preg_replace('/(?<=\w).*/', '', $n), $parts);
        return implode('', $letters);
    }

    private function truncate(?string $s, int $len): ?string
    {
        if (!$s) { return $s; }
        return mb_strlen($s) > $len ? mb_substr($s, 0, $len) : $s;
    }

    private function parseBirthYear(?string $raw): ?int
    {
        if (!$raw) { return null; }
        // Grab the first 4-digit year between 1900 and 2100
        if (preg_match('/(19|20)\d{2}/', $raw, $m)) {
            return (int)$m[0];
        }
        return null;
    }

    private function validateOrFail(object $entity, SymfonyStyle $io): void
    {
        return;
        $errors = $this->validator->validate($entity);
        if (\count($errors) > 0) {
            dump($entity);
            foreach ($errors as $e) {
                $io->error($e->getPropertyPath() . ' / ' . $e->getMessage());
            }
            throw new \RuntimeException('Validation failed.');
        }
    }
}
