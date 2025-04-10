<?php

namespace App\Controller;

use Inspector\Inspector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(BASE_HOST)%')] private ?string $baseHost = null,
    ) {
    }

    #[Route('/', name: 'app_landing', priority: -300)]
//    #[Route('/{_locale<%app.supported_locales%>}/', name: 'app_landing_locale', priority: 300)]
    public function index(Request $request,
        Inspector $inspector,
        ?string $_format = null,
        ?string $_locale = null,  // to override
    ): Response {
        $redirect = $this->redirectToRoute('app_homepage');

        return $redirect;
    }
}
