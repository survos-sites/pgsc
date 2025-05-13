<?php

namespace App\Command;

use App\Dto\ArtistDto;
use App\Entity\Artist;
use App\Entity\Location;
use App\Enum\LocationType;
use App\Factory\ArtistFactory;
use App\Factory\LocationFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\SaisBundle\Model\ProcessPayload;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function Symfony\Component\String\u;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[AsCommand('app:load', 'Load the chijal data')]
class LoadCommand
{
	public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface  $objectMapper,
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
            dd($artistData);
//            $initials = $artistData['code'];
//            $email = $initials.'@test.com';
            $email = $artistData['Email Address'];
            if (!$email) {
                dd($artistData);
                continue;
            }
            $artistData = (object) $artistData;
            $artistData->id = null;
            dump($artistData);
            $code = u($email)->before('@')->toString();
            $artist = new Artist();
            $this->objectMapper->map($artistData, $artist);
            dd($artistData, $artist);


                //            dd($artistData);

            // OR create user with role ARTIST?
            $artist = ArtistFactory::createOne([
                'name' => $artistData['name'],
                'code' => $code,
                'bio' => $artistData['bio'],
                'slogan' => $artistData['slogan'],
                'phone' => $artistData['phone'],
                'driveUrl' => $artistData['foto'],
                'email' => $email,
            ]);
//            if ($artist->getDriveUrl()) {
//                $response = $this->saisClientService->dispatchProcess(new ProcessPayload(
//                    'chijal',
//                    [
//                        $artist->getDriveUrl(),
//                    ]
//                ));
//                $artist->setImages($response[0]['resized']??null);
//
//            }
            UserFactory::createOne([
                'code' => $code,
                'email' => $email,
                'cel' => $artistData['phone'],
                'plainPassword' => 'test',
                'roles' => ['ROLE_USER', 'ROLE_ARTIST'],
            ]);
            $artists[] = $artist;
        }

        foreach ($this->locations() as $row) {
            if ($row['status'] === 'inactivo') {
                continue;
            }
            LocationFactory::createOne([
                'name' => ($name = trim($row['nombre'])),
                'address' => $row['direcciones'],
                'type' => LocationType::from(trim(strtolower($row['tipo']))) ?? null,
                'code' => $row['codigo'] ?: $this->initials($name),
//                'lat' => $row['lat'] ? (float) $row['lat'] : null,
//                'lng' => $row['lon'] ? (float) $row['lon'] : null,
            ]);
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

        $csv = Reader::createFromPath('data/artist-responses.csv', 'r');
        $csv->setHeaderOffset(0);
        return $csv->getRecordsAsObject(ArtistDto::class);
        foreach ($csv->getRecords() as $record) {
            $email = $record['Email Address'];
            $responses[$email] = $record;
        }

        $csv = Reader::createFromPath('data/artists.csv', 'r');
        $csv->setHeaderOffset(0);
        foreach ($csv->getRecordsAsObject() as $record) {
            dd($record);

        }

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
