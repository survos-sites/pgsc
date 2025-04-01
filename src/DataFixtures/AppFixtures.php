<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\User;
use App\Enum\LocationType;
use App\Factory\ArtistFactory;
use App\Factory\LocationFactory;
use App\Factory\ObraFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use League\Csv\Reader;

use function Symfony\Component\String\u;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
//        ArtistFactory::createMany(2);00
//        LocationFactory::createMany(2);
        $artists = [];
        foreach ($this->artists() as $artistData) {
            $initials = $artistData['code'];
            $email =  $initials . '@test.com';
//            dd($artistData);
            // OR create user with role ARTIST?
            $artist = ArtistFactory::createOne(['name' => $artistData['nombre'],
                'code' => $initials,
                'email' => $email,
                ]);
            UserFactory::createOne([
                'code' => $initials,
                'email' => $email,
                'cel' => $artistData['whatsapp'],
                'plainPassword' => 'test',
                'roles' => ['ROLE_USER', 'ROLE_ARTIST'],
            ]);
            $artists[] = $artist;
        }
        foreach ($this->locations() as $row) {
            LocationFactory::createOne([
                'name' => ($name = trim($row['nombre'])),
                'type' => LocationType::from(trim(strtolower($row['tipo']))) ?? null,
                'code' => $row['code'] ?: $this->initials($name),
                'lat' => $row['lat'] ? (float)$row['lat'] : null,
                'lng' => $row['lon'] ? (float)$row['lon'] : null,
            ]);
        }

        ObraFactory::createMany(
            85,
            fn() =>
            // note the callback - this ensures that each of the artwork has a locations and artists.  @todo: null locations
                 [
                    'artist' => ArtistFactory::random(), //  array_rand($artists)[rand(0, count($artists))],  // ::random(),
                    'location' => LocationFactory::random()
                 ]// each comment set to a random Post from those already in the database
        );

        UserFactory::createOne([
            'code' => 'superadmin',
                'email' => 'superadmin@example.com',
                'plainPassword' => 'adminpass',
                'roles' => ['ROLE_SUPER_ADMIN'],
            ]);

        foreach (['tacman@gmail.com','yarenivillada@gmail.com'] as $email) {
            UserFactory::createOne([
                'code' => str_replace('@gmail.com','',$email),
                'email' => $email,
                'plainPassword' => 'batsi',
                'roles' => ['ROLE_ADMIN'],
            ]);
        }

        UserFactory::createOne([
            'code' => 'admin',
            'email' => 'admin@test.com',
            'plainPassword' => 'admin',
            'roles' => ['ROLE_ADMIN'],
        ]);

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
        $csv = Reader::createFromPath('data/artists.csv', 'r');
        $csv->setHeaderOffset(0);
        return $csv->getRecords();
    }

    private function locations(): iterable
    {
        $csv = Reader::createFromPath('data/locations.csv', 'r');
        $csv->setHeaderOffset(0);
        return $csv->getRecords();
//
//        $header = $csv->getHeader(); //returns the CSV header record
//
////returns all the records as
//        $records = $csv->getRecords(); // an Iterator object containing arrays
//
//        return explode("\n", <<<END
//Centro cultural Carlos Jurado
//La Enseñanza Casa de la Ciudad
//Galeria Arteria
//Cerro Brujo
//Museo de los Altos de Chiapas
//El Caminante
//Galeria Taxcalate
//Sabi
//MUY
//END
//        );
    }

    private function initials(string $name): string
    {
        $name = u($name)->ascii()->toString();
        return strtolower(implode('', array_map(function ($name) {
            return preg_replace('/(?<=\w)./', '', $name);
        }, explode(' ', $name))));
    }
}
