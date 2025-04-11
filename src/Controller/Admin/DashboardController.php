<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
use App\Entity\Sacro;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use function Symfony\Component\Translation\t;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private LocationRepository $locationRepository,
        private ObraRepository $obraRepository,
        private UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->showEntityActionsInlined();
    }

    public function index(): Response
    {
        $myMap = (new Map());
        $myMap
            // Explicitly set the center and zoom
//                ->center($point)
            ->zoom(16)
            // Or automatically fit the bounds to the markers
            ->fitBoundsToMarkers()

        ;
        foreach ($this->locationRepository->findAll() as $location) {
            if ($location->getLat()) {
                $point = new Point($location->getLat(), $location->getLng());
                $myMap->addMarker(new Marker(
                    position: $point,
                    title: $location->getName(),
                ));
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'artists' => $this->artistRepository->findAll(),
            'locations' => $this->locationRepository->findAll(),
            'myMap' => $myMap,
        ]);

        //        return $this->render('admin/dashboard.html.twig', [
        //            'locations' => $this->locationRepository->findAll(),
        //            'artists' => $this->artistRepository->findAll(),
        //            'obras' => $this->obraRepository->findAll(),
        //        ]);

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->renderSidebarMinimized(false)
//            ->setTitle('Chijal')
            ->setLocales(['es','en'])
            ->setTitle('<img src="/chijal-landscape.png" />');
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('dashboard', 'tabler:home');
        yield MenuItem::linkToCrud('artists', 'tabler:list', Artist::class)
            ->setBadge($this->artistRepository->count())
        ;
        yield MenuItem::linkToCrud('locations', 'tabler:location', Location::class)
            ->setBadge($this->locationRepository->count())
        ;

        yield MenuItem::linkToCrud('objects', 'ri:image-line', Obra::class)
            ->setBadge($this->obraRepository->count())
        ;
        yield MenuItem::linkToCrud('sacro', 'ri:image-line', Sacro::class)
            ->setBadge($this->obraRepository->count())
        ;
        //         yield MenuItem::linkToRoute('home', 'tabler:home', 'app_homepage');

        //        yield
        //            MenuItem::subMenu('Blog', 'tabler:home')->setSubItems(
        //                [
        //                MenuItem::linkToCrud('Artwork', 'tabler:home', Obra::class),
        //                //                MenuItem::linkToCrud('Posts', 'tabler:home', Obra::class),
        //                ]
        //            );
        // ...

        yield MenuItem::section('external');
        yield MenuItem::linkToUrl('Issues', 'tabler:brand-github', 'https://github.com/survos-sites/pgsc/issues')
            ->setLinkTarget(
                '_blank'
            );
        yield MenuItem::linkToUrl('Sync', 'mdi:sync', $this->urlGenerator->generate('app_sync'));

        foreach ([
            'https://docs.google.com/spreadsheets/d/1osvCYhAahpZ3p1p_xT923MFzDXT2-NdF2qhlz91Btjs/edit?gid=0#gid=0' => 'artists',
            'https://docs.google.com/spreadsheets/d/1osvCYhAahpZ3p1p_xT923MFzDXT2-NdF2qhlz91Btjs/edit?gid=1012778928#gid=1012778928' => 'locations',
            'https://docs.google.com/forms/d/1BFR_-et-H9Rmy1a-7BgxlJb7cfHHtxzvGq2ULV5yuE4/viewform?edit_requested=true' => 'ubi form',
            'https://docs.google.com/forms/d/1luEs8p2KULaaJc6EQk_h9NA8ZrU5nm_JShrX7laEk8w/viewform?edit_requested=true' => 'artist form',


        ] as $link => $label) {
            yield MenuItem::linkToUrl($label, 'arcticons:google-sheets', $link)
                ->setLinkTarget(
                    '_blank'
                );
        }
        https:// docs.google.com/spreadsheets/d/1osvCYhAahpZ3p1p_xT923MFzDXT2-NdF2qhlz91Btjs/edit?gid=0#gid=0
        ;

        //        yield MenuItem::linkToRoute('login', 'tabler:login', 'app_login');
        yield MenuItem::section('account');
        if ($this->security->isGranted('ROLE_USER')) {
            yield MenuItem::linkToUrl('logout', 'tabler:logout', $this->urlGenerator->generate('app_login'));
            yield MenuItem::linkToUrl('profile', 'tabler:user', $this->urlGenerator->generate('oauth_profile'));
        } else {
            yield MenuItem::linkToUrl('login', 'tabler:login', $this->urlGenerator->generate('app_login'));
        }

        yield MenuItem::section('shortcuts');
        $filters = [];
        foreach ($this->locationRepository->findAll() as $location) {
            $filters[] =
                MenuItem::linkToCrud($location->getName(), null, Obra::class)
                ->setQueryParameter('filters[location][comparison]', '=')
                ->setQueryParameter('filters[location][value]', $location->getId())
            ;
        }
        yield MenuItem::subMenu(t('by.location'), 'tabler:building')->setSubItems($filters);

        $filters = [];
        foreach ($this->artistRepository->findAll() as $entity) {
            $filters[] =
                MenuItem::linkToCrud($entity->getName(), null, Obra::class)
                    ->setQueryParameter('filters[artist][comparison]', '=')
                    ->setQueryParameter('filters[artist][value]', $entity->getId())
            ;
        }
        yield MenuItem::subMenu(t('by.artist'), 'tabler:user')->setSubItems($filters);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->useCustomIconSet()
        ;
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
