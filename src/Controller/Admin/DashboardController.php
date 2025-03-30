<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Entity\Location;
use App\Entity\Obra;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{

    public function __construct(
        private ArtistRepository $artistRepository,
        private LocationRepository $locationRepository,
        private ObraRepository $obraRepository,
    )
    {
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->showEntityActionsInlined();
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');

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
            ->setTitle('PG de SanCris');
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
         yield MenuItem::linkToRoute('home', 'tabler:home',  'app_homepage');

        yield MenuItem::section('Blog');
        yield
            MenuItem::subMenu('Blog', 'tabler:home')->setSubItems(
              [
                MenuItem::linkToCrud('Artwork', 'tabler:home', Obra::class),
//                MenuItem::linkToCrud('Posts', 'tabler:home', Obra::class),
              ]);
            // ...

        yield MenuItem::linkToUrl('Issues', 'tabler:brand-github', 'https://github.com/survos-sites/pgsc/issues')
            ->setLinkTarget(
                '_blank'
            );
        ;


        foreach ($this->locationRepository->findAll() as $location) {
            $filters[] =
                MenuItem::linkToCrud($location->getName(), null, Obra::class)
                ->setQueryParameter('filters[location][comparison]', '=')
                ->setQueryParameter('filters[location][value]', $location->getId())
            ;
        }
        yield MenuItem::subMenu('By Location', 'tabler:location')->setSubItems($filters);

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
