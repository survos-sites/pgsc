<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\ArtistFactory;
use App\Factory\LocationFactory;
use App\Factory\ObraFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use function Symfony\Component\String\u;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
//        ArtistFactory::createMany(2);
//        LocationFactory::createMany(2);
        foreach ($this->names() as $name) {
            ArtistFactory::createOne(['name' => $name,
                'code' => $this->initials($name)
                ]);
        }
        foreach ($this->locations() as $name) {
            LocationFactory::createOne([
                'name' => $name,
                'code' => $this->initials($name)
            ]);
        }

        ObraFactory::createMany(
            5, // create 5 comments
            function() { // note the callback - this ensures that each of the 5 comments has a different Post
                return [
                    'artist' => ArtistFactory::random(),
                    'location' => LocationFactory::random()]; // each comment set to a random Post from those already in the database
            }
        );

        UserFactory::createOne([
                'email' => 'superadmin@example.com',
                'plainPassword' => 'adminpass',
                'roles' => ['ROLE_SUPER_ADMIN'],
            ]);

        UserFactory::createOne([
            'email' => 'admin@example.com',
            'plainPassword' => 'adminpass',
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

        $manager->flush();
    }

    private function names(): array
    {
        return explode("\n", <<<END
Juan Chawuk
Luis Herrera 
Cristian Espinosa
Enrique Peko
Alejandra Gutierrez
Maruch
Fonky Eduardo 
Margarita Martinez
Teraz
Anton Vazquez 
Peta Joel
END
        );

    }

    private function locations(): array
    {
        return explode("\n", <<<END
Centro cultural Carlos Jurado
La Enseñanza Casa de la Ciudad 
Galeria Arteria
Cerro Brujo 
Museo de los Altos de Chiapas 
El Caminante
Galeria Taxcalate 
Sabi 
MUY
END
        );
    }

    private function initials(string $name): string
    {
        $name = u($name)->ascii()->toString();
        return implode('', array_map(function ($name) {
            return preg_replace('/(?<=\w)./', '', $name);
        }, explode(' ', $name)));

    }


}
