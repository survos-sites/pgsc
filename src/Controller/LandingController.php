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
        ?string $name = null,
    ) {
        //        parent::__construct($name);
    }

    #[Route('/', name: 'app_landing', priority: -300)]
    //    #[Route('/wp-content{badUrl}', name: 'bad_prefix', requirements: ['badUrl' => '.+'], methods: ['GET'], priority: 1)]
    //    #[Route('/{badUrl}.{_format}', name: 'landing_catch_php',
    //        requirements: ['badUrl' => '.+', '_format' => 'php|py'], methods: ['GET'], priority: 1)]
    //    #[Route('/{_locale<%app.supported_locales%>}/', name: 'app_landing', priority: 300)]
    public function index(Request $request,
        Inspector $inspector,
        ?string $_format = null,
        ?string $_locale = null,  // to override
    ): Response {
        $redirect = $this->redirectToRoute('admin');

        return $redirect;
    }
}
