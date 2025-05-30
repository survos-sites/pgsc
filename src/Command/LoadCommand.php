<?php

namespace App\Command;

use App\Dto\ArtistDto;
use App\Entity\Artist;
use App\Entity\Location;
use App\Enum\LocationType;
use App\Factory\ArtistFactory;
use App\Factory\LocationFactory;
use App\Factory\UserFactory;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function Symfony\Component\String\u;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[AsCommand('app:load', 'Load the chijal data')]
class LoadCommand
{
	public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface  $objectMapper,
        private readonly ArtistRepository $artistRepository,
        private readonly LocationRepository $locationRepository,
        private readonly SaisClientService $saisClientService, // @todo: move to workflow
        private readonly ObraRepository $obraRepository,
        private readonly ValidatorInterface $validator,
    )
	{
	}


	public function __invoke(
		SymfonyStyle $io,
		#[Option('refresh the cached data from google sheets')]
		?bool $refresh = null,
	): int
	{
        $manager = $this->entityManager;
		if ($refresh) {
		    $io->writeln("Option refresh: $refresh");
		}

        $artists = [];
        foreach ($this->artists() as $artistData) {
//            $initials = $artistData['code'];
//            $email = $initials.'@test.com';
            if (!$email = $artistData['email']) {
                dd($artistData);
                continue;
            }
//            $artistData = (object) $artistData;
//            $artistData->id = null;
            if (!$artist = $this->artistRepository->findOneBy(['email' => $email])) {
                $artist = new Artist();
                $this->entityManager->persist($artist);
                $artist->setEmail($email);

//                UserFactory::createOne([
//                    'code' => $artist->getCode(),
//                    'email' => $artist->getEmail(),
////                'cel' => $artistData['phone'],
//                    'plainPassword' => 'test',
//                    'roles' => ['ROLE_USER', 'ROLE_ARTIST'],
//                ]);

            }
            $artist->setName($artistData['name'])
                ->setCode($artistData['code'])
                ->setSlogan(substr($artistData['tagline'], 0, 80))
                ->setBio($artistData['bio'])
                ->setDriveUrl($artistData['driveUrl'])
                ->setBio($artistData['long_bio'])
//                ->setLanguages($artistData['languages'])
                ->setBirthYear($artistData['nacimiento']);
            if ($artist->getDriveUrl()) {
                $response = $this->saisClientService->dispatchProcess(new ProcessPayload(
                    'chijal',
                    [
                        $artist->getDriveUrl(),
                    ]
                ));
                $artist->setImages($response[0]['resized'] ?? null);
            }
            $errors = $this->validator->validate($artist);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $io->error($error->getPropertyPath()  . "/" . $error->getMessage());
                }
                dd();
            }
//                $this->objectMapper->map($artistData, $artist);
//                dd($artistData, $artist);
//            $code = u($email)->before('@')->toString();


                //            dd($artistData);

            // OR create user with role ARTIST?
//            $artist = ArtistFactory::createOne([
//                'name' => $artistData['name'],
//                'code' => $code,
//                'bio' => $artistData['bio'],
//                'slogan' => $artistData['slogan'],
//                'phone' => $artistData['phone'],
//                'driveUrl' => $artistData['foto'],
//                'email' => $email,
//            ]);
//
//            }
            $artists[] = $artist;
        }

        foreach ($this->locations() as $row) {
            if ($row['status'] === 'inactivo') {
                continue;
            }
            if (!$location = $this->locationRepository->findOneBy(['name' => $row['nombre']])) {
                $location = new Location();
                $this->entityManager->persist($location);
                $location->setName($row['nombre']);
            }
            $location->setStatus($row['status'])
                ->setCode($row['codigo'])
                ->setAddress($row['direcciones'])
                ;
//                ->setType($row['tipo'])

//                    'name' => ($name = trim($row['nombre'])),
//                    'status' => $row['status'],
//                    'address' => $row['direcciones'],
//                    'type' => LocationType::from(trim(strtolower($row['tipo']))) ?? null,
//                    'code' => $row['codigo'] ?: $this->initials($name),
//                'lat' => $row['lat'] ? (float) $row['lat'] : null,
//                'lng' => $row['lon'] ? (float) $row['lon'] : null,
//                ]);

//            }
        }
        $manager->flush();

        foreach ($manager->getRepository(Location::class)->findAll() as $location) {
            $location->setObraCount($location->getObras()->count());
        }
        foreach ($manager->getRepository(Artist::class)->findAll() as $location) {
            $location->setObraCount($location->getObras()->count());
        }
        $manager->flush();

        $io->success(self::class . " success.");
		return Command::SUCCESS;
	}



    private function artists(): iterable
    {


        $csv = Reader::createFromPath('data/artistas.csv', 'r');
        $csv->setHeaderOffset(0);
        foreach ($csv->getRecords() as $record) {
            if ($email = $record['email']) {
                $ourData[$email] = $record;
            }
        }
//        return $csv->getRecordsAsObject(ArtistDto::class);

        // the responses from Google Form, @artists
        // https://support.google.com/docs/thread/223250855/how-do-i-shorten-google-form-headers-in-sheets-so-the-column-header-form-question-is-easy-to-read?hl=en

        $csv = Reader::createFromPath('data/artists.csv', 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            if ($email = $record['email']) {
                $combined = array_merge($ourData[$email], $record);
                $responses[$email] = $combined;
            }
        }
        return $responses;


        return $csv->getRecords();
    }

    private function locations(): iterable
    {
        $csv = Reader::createFromPath('data/locations.csv', 'r');
        $csv->setHeaderOffset(0);

        return $csv->getRecords();
    }

    private function initials(string $name): string
    {
        $name = u($name)->ascii()->toString();

        return strtolower(implode('', array_map(function ($name) {
            return preg_replace('/(?<=\w)./', '', $name);
        }, explode(' ', $name))));
    }
}
