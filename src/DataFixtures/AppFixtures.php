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

    }


}
