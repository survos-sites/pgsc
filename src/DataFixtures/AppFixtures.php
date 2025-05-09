<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\User;
use App\Enum\LocationType;
use App\Factory\ArtistFactory;
use App\Factory\LocationFactory;
use App\Factory\ObraFactory;
use App\Factory\SacroFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use League\Csv\Reader;

use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
use function Symfony\Component\String\u;

class AppFixtures extends Fixture
{
    public function __construct(
        private SaisClientService $saisClientService,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        // admins
        UserFactory::createOne([
            'code' => 'superadmin',
            'email' => 'superadmin@example.com',
            'plainPassword' => 'adminpass',
            'roles' => ['ROLE_SUPER_ADMIN'],
        ]);

        foreach (['tacman@gmail.com', 'yarenivillada@gmail.com'] as $email) {
            UserFactory::createOne([
                'code' => str_replace('@gmail.com', '', $email),
                'email' => $email,
                'plainPassword' => 'tt',
                'roles' => ['ROLE_ADMIN'],
            ]);
        }

        UserFactory::createOne([
            'code' => 'admin',
            'email' => 'admin@test.com',
            'plainPassword' => 'admin',
            'roles' => ['ROLE_ADMIN'],
        ]);


        // $product = new Product();
        // $manager->persist($product);
        //        ArtistFactory::createMany(2);00
        //        LocationFactory::createMany(2);
        $artists = [];
        foreach ($this->artists() as $artistData) {
//            $initials = $artistData['code'];
//            $email = $initials.'@test.com';
            $email = $artistData['Email Address'];
            if (!$email) {
                dd($artistData);
                continue;
            }
            dump($artistData);
            $code = u($email)->before('@')->toString();
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
            if ($artist->getDriveUrl()) {
                $response = $this->saisClientService->dispatchProcess(new ProcessPayload(
                    'chijal',
                    [
                        $artist->getDriveUrl(),
                    ]
                ));
                $artist->setImages($response[0]['resized']??null);

            }
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
        return;

        ObraFactory::createMany(
            85,
            fn () =>
            // note the callback - this ensures that each of the artwork has a locations and artists.  @todo: null locations
                 [
                     'artist' => ArtistFactory::random(), //  array_rand($artists)[rand(0, count($artists))],  // ::random(),
                     'location' => LocationFactory::random(),
                 ]// each comment set to a random Post from those already in the database
        );


        //        UserFactory::new()
        //            ->withAttributes([
        //                'email' => 'admin@example.com',
        //                'plainPassword' => 'adminpass',
        //            ])
        //            ->promoteRole('ROLE_ADMIN')
        //            ->create();
        //
        //        UserFactory::new()
        //            ->withAttributes([
        //                'email' => 'moderatoradmin@example.com',
        //                'plainPassword' => 'adminpass',
        //            ])
        //            ->promoteRole('ROLE_MODERATOR')
        //            ->create();
        //
        //        UserFactory::new()
        //            ->withAttributes([
        //                'email' => 'tisha@symfonycasts.com',
        //                'plainPassword' => 'tishapass',
        //                'firstName' => 'Tisha',
        //                'lastName' => 'The Cat',
        //                'avatar' => 'tisha.png',
        //            ])
        //            ->create();

        foreach ($manager->getRepository(Location::class)->findAll() as $location) {
            $location->setObraCount($location->getObras()->count());
        }
        foreach ($manager->getRepository(Artist::class)->findAll() as $location) {
            $location->setObraCount($location->getObras()->count());
        }
        $manager->flush();
    }

    private function artists(): iterable
    {

        $csv = Reader::createFromPath('data/artist-responses.csv', 'r');
        $csv->setHeaderOffset(0);
        return $csv->getRecords();
        foreach ($csv->getRecords() as $record) {
            $email = $record['Email Address'];
            $responses[$email] = $record;
        }

        $csv = Reader::createFromPath('data/artists.csv', 'r');
        $csv->setHeaderOffset(0);
        foreach ($csv->getRecords() as $record) {
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
