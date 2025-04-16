<?php

namespace App\Menu;

use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use Survos\BootstrapBundle\Event\KnpMenuEvent;
use Survos\BootstrapBundle\Service\MenuService;
use Survos\BootstrapBundle\Traits\KnpMenuHelperInterface;
use Survos\BootstrapBundle\Traits\KnpMenuHelperTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

// events are
/*
// #[AsEventListener(event: KnpMenuEvent::NAVBAR_MENU2)]
#[AsEventListener(event: KnpMenuEvent::SIDEBAR_MENU, method: 'sidebarMenu')]
#[AsEventListener(event: KnpMenuEvent::PAGE_MENU, method: 'pageMenu')]
#[AsEventListener(event: KnpMenuEvent::FOOTER_MENU, method: 'footerMenu')]
#[AsEventListener(event: KnpMenuEvent::AUTH_MENU, method: 'appAuthMenu')]
*/

final class AppMenu implements KnpMenuHelperInterface
{
    use KnpMenuHelperTrait;

    public function __construct(
        #[Autowire('%kernel.environment%')] protected string $env,
        private ArtistRepository $artistRepo,
        private LocationRepository $locationRepo,
        private ObraRepository $obraRepo,
        private MenuService $menuService,
        private Security $security,
        private ?AuthorizationCheckerInterface $authorizationChecker = null,
    ) {
    }

    public function appAuthMenu(KnpMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $this->menuService->addAuthMenu($menu);
    }

    #[AsEventListener(event: KnpMenuEvent::NAVBAR_MENU2)]
    public function navbarMenu(KnpMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $options = $event->getOptions();

        $this->add($menu, 'app_homepage');
        $this->add($menu, 'api_doc', label: 'API');
        $this->add($menu, 'app_sync');
        // for nested menus, don't add a route, just a label, then use it for the argument to addMenuItem

        $subMenu = $this->addSubmenu($menu, 'sacro');
        array_map(fn($route) => $this->add($subMenu, $route), ['cmas_index','cmas_import','cmas_images']);
        $subMenu = $this->addSubmenu($menu, 'debug');
        if ('dev' === $this->env) {
            $this->add($subMenu, 'survos_workflows');
            $this->add($subMenu, 'survos_crawler_data');
        }

        $nestedMenu = $this->addSubmenu($menu, 'artists');
        foreach ($this->artistRepo->findAll() as $artist) {
            $this->add($nestedMenu, 'artist_show', $artist,
                translationDomain: false,
                label: $artist->getName(),
                badge: $artist->getObraCount());
        }
        $nestedMenu = $this->addSubmenu($menu, 'locations');
        foreach ($this->locationRepo->findAll() as $location) {
            $this->add($nestedMenu, 'location_show', $location,
                translationDomain: false,
                label: $location->getName(), badge: $location->getObraCount());
        }

        $nestedMenu = $this->addSubmenu($menu, 'artwork');
        foreach (['by_location', 'by_artist'] as $grouping) {
            $this->add($nestedMenu, 'app_homepage', label: $grouping);
        }
        $this->add($menu, 'admin', translationDomain: false, label: 'EZ');
        $this->appAuthMenu($event);
    }
}
