<?php

namespace App\Factory;

use App\Entity\Artist;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Artist>
 */
final class ArtistFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Artist::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        foreach (['facebook', 'twitter', 'instagram'] as $type) {
            $social[] = "https://{$type}.com/".self::faker()->slug(1);
        }
        $name = self::faker()->name();

        return [
            'socialMedia' => join("\n", $social),
            'studioVisitable' => self::faker()->randomElement(Artist::STUDIO_VISITABLE),
            //            'email' => self::faker()->email(),
            //            'bio' => self::faker()->paragraph(3),
            'birthYear' => self::faker()->numberBetween(1950, 2009),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Artist $artist): void {})
        ;
    }
}
