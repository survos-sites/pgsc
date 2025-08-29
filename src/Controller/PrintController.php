<?php

namespace App\Controller;

use App\Repository\ObraRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrintController extends AbstractController
{
    public function __construct(
        private ObraRepository $obraRepository,
    )
    {
    }

    #[Route('/labels', name: 'app_labels')]
    public function index(): Response
    {
        return $this->render('print/labels.html.twig', [
            'obras' => $this->obraRepository->findBy(['year' => date('Y')]),
        ]);
    }
}
