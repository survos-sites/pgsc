<?php

namespace App\Factory;

use App\Entity\Obra;
use Bluemmb\Faker\PicsumPhotosProvider;
use Faker\Provider\FakeCar;
use FakerRestaurant\Provider\es_PE\Restaurant;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use function Symfony\Component\String\u;

/**
 * @extends PersistentProxyObjectFactory<Obra>
 */
final class ObraFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private SluggerInterface $asciiSlugger,
    )
    {
    }

    public static function class(): string
    {
        return Obra::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {

        $faker = self::faker();
        $faker->addProvider(new PicsumPhotosProvider($faker));
//        $faker->addProvider(EnglishWord::class);
        $faker->addProvider(new FakeCar($faker));
        $faker->addProvider(new Restaurant($faker));
        $title = join(' ', self::faker()->words(3));

        $title = sprintf('%s, %s y %s', $faker->vegetableName(), $faker->beverageName(), $faker->foodName());
        $title = u($title)->title(true);
//        $title = $faker->vehicle() . '/' . $faker->veh;
//        $title = $faker->words()
        return [
            'artist' => ArtistFactory::new(),
            'title' => $title,
            'description' => "algo de " . $title . "\n\n" . $faker->paragraph(3, true),
            'height' => self::faker()->numberBetween(10, 120),
            'width' => self::faker()->numberBetween(10, 120),
            'depth' => self::faker()->numberBetween(1, 20),
            'year' => self::faker()->numberBetween(2017, 2024),
            'price' => self::faker()->numberBetween(10, 40) * 100,
            'materials' => $faker->randomElement(['oro', 'plata', 'carton','oleo', 'tela','papel']),
            'code' => strtolower($this->asciiSlugger->slug($title)),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Obra $obra): void {})
        ;
    }
}
