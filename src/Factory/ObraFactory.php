<?php

namespace App\Factory;

use App\Entity\Obra;
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
        $title = join(' ', self::faker()->words(3));
        $title = u($title)->title(true);
        return [
            'artist' => ArtistFactory::new(),
            'title' => $title,
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
