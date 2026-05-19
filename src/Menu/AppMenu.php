<?php

declare(strict_types=1);

namespace App\Menu;

use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Survos\TablerBundle\Service\IconService;
use Survos\TablerBundle\Service\RouteAliasService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\RouterInterface;

final class AppMenu
{
    use MenuBuilderTrait;

    public function __construct(
        #[Autowire('%kernel.environment%')] private string $env,
        private ArtistRepository $artistRepo,
        private LocationRepository $locationRepo,
        private ObraRepository $obraRepo,
        private Security $security,
        protected ?RouterInterface $router = null,
        protected ?RouteAliasService $routeAliasService = null,
        protected ?IconService $iconService = null,
    ) {}

    #[AsEventListener(event: MenuEvent::NAVBAR_MENU)]
    public function onNavbar(MenuEvent $event): void
    {
        $menu = $event->getMenu();

        $this->add($menu, 'app_homepage');
        $this->add($menu, 'app_sync', label: 'Sync', icon: 'mdi:sync');
        $this->add($menu, 'admin', label: 'Admin', icon: 'tabler:dashboard');

        $labelsMenu = $this->addSubmenu($menu, 'Labels', 'tabler:printer');
        foreach (['obra', 'artist', 'location'] as $shortClass) {
            $this->add($labelsMenu, 'print_labels_config', ['shortClass' => $shortClass], label: ucfirst($shortClass) . ' labels');
        }

        $artistMenu = $this->addSubmenu($menu, 'Artists', 'tabler:user');
        foreach ($this->artistRepo->findAll() as $artist) {
            $this->add($artistMenu, 'artist_show', $artist,
                translationDomain: false,
                label: $artist->name,
                badge: $artist->obraCount);
        }

        $locationMenu = $this->addSubmenu($menu, 'Locations', 'tabler:location');
        foreach ($this->locationRepo->findAll() as $location) {
            $this->add($locationMenu, 'location_show', $location,
                translationDomain: false,
                label: $location->name,
                badge: $location->obraCount);
        }

        if ('dev' === $this->env) {
            $devMenu = $this->addSubmenu($menu, 'Dev', 'tabler:code');
            $this->add($devMenu, 'api_doc', label: 'API');
            $this->add($devMenu, 'survos_commands');
        }
    }

    #[AsEventListener(event: MenuEvent::ADMIN_NAVBAR_MENU)]
    public function onAdminNavbar(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $this->add($menu, 'app_sync', label: 'Sync', icon: 'mdi:sync');
    }
}
