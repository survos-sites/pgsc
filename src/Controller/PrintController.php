<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\LabelsConfigType;
use App\Repository\ArtistRepository;
use App\Repository\LocationRepository;
use App\Repository\ObraRepository;
use App\Service\SyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class PrintController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(GRIST_HOST)%')] private readonly ?string $gristHost,
        #[Autowire('%env(GRIST_DOC_ID)%')] private readonly ?string $gristDocId,
        private ObraRepository $obraRepository,
        private LocationRepository $locationRepository,
        private ArtistRepository $artistRepository,
        private SyncService $syncService,
    ) {
    }

    /**
     * Config form shown before printing.
     * Collects exhibition + options, then redirects to the print route.
     */
    #[Route('/labels/{shortClass}/config', name: 'print_labels_config')]
    public function labelsConfig(
        string $shortClass,
        Request $request,
    ): Response {
        $exhibitions = $this->syncService->getExhibitions();

        $form = $this->createForm(LabelsConfigType::class, null, [
            'exhibitions' => $exhibitions,
            'short_class' => $shortClass,
            'action'      => $this->generateUrl('print_labels_config', ['shortClass' => $shortClass]),
            'method'      => 'GET',
        ]);

        // Only treat this as a submission when the query actually carries the form.
        // It is a GET form, so without this guard the first visit counts as an empty
        // submission, render() sees a submitted-and-invalid form, and the page Kryzia
        // starts from answers 422 -- it draws correctly, which is why it went unnoticed.
        if ($request->query->has($form->getName())) {
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $params = ['shortClass' => $shortClass];
            if (!empty($data['exhibition'])) {
                $params['exhibition'] = $data['exhibition'];
            }
            if (!empty($data['showQrCode'])) {
                $params['showQrCode'] = '1';
            }

            return $this->redirectToRoute('print_labels', $params);
        }

        $spreadsheetId  = $this->syncService->getSpreadsheetId();
        $spreadsheetUrl = $spreadsheetId
            ? 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId
            : null;

        return $this->render('print/labels_config.html.twig', [
            'form'           => $form,
            'shortClass'     => $shortClass,
            'exhibitions'    => $exhibitions,
            'spreadsheetUrl' => $spreadsheetUrl,
            'syncUrl'        => $this->generateUrl('app_sync'),
            // The spreadsheet is still what app:sync reads, but Grist is where the data
            // is actually edited now -- so the page offers both rather than only the one
            // the sync happens to use.
            'gristUrl'       => ($this->gristHost && $this->gristDocId)
                ? rtrim($this->gristHost, '/') . '/o/docs/' . $this->gristDocId
                : null,
        ]);
    }

    /**
     * The actual print page — renders labels ready for the browser's print dialog.
     */
    #[Route('/labels/{shortClass}', name: 'print_labels')]
    public function labels(
        string $shortClass,
        #[MapQueryParameter] ?string $loc        = null,
        #[MapQueryParameter] ?string $exhibition = null,
        #[MapQueryParameter] ?bool   $showQrCode = null,
    ): Response {
        $showQrCode ??= true;

        if ($loc) {
            $obras = $this->locationRepository->find($loc)->obras->toArray();
        } elseif ($exhibition) {
            $obras = $this->obraRepository->findBy(['exhibition' => $exhibition]);
        } else {
            $obras = $this->obraRepository->findAll();
        }

        $entities = match ($shortClass) {
            'obra'     => $obras,
            'location' => $this->locationRepository->findAll(),
            // 'artist', singular -- AppMenu builds these links from
            // ['obra', 'artist', 'location'], so 'artists' matched nothing and artist
            // labels fell through to default and printed obras instead.
            'artist'   => $this->artistRepository->findAll(),
            default    => $obras,
        };

        $template = match ($shortClass) {
            'location' => 'print/location-labels.html.twig',
            'artist'   => 'print/artist-labels.html.twig',
            default    => 'print/obras.html.twig',
        };

        return $this->render($template, [
            'entities'    => $entities,
            'obras'       => $entities,
            'locations'   => $entities,
            'artists'     => $entities,
            'showQrCode'  => $showQrCode,
            'exhibition'  => $exhibition,
        ]);
    }
}
