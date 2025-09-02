<?php

namespace App\Controller;

use App\Service\GoogleDocParserService;
use Survos\GoogleSheetsBundle\Service\SheetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Google_Client;
use Google_Service_Docs;
final class LosAltosController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(JSON_AUTH)%')]
        private readonly string $jsonAuth,


    )
    {
    }

    #[Route('/losaltos', name: 'app_losaltos')]
    public function losaltos(
        SheetService              $sheetService,
        GoogleDocParserService    $parser,
        #[MapQueryParameter] bool $refresh = false,
    ): Response {
        $url = 'https://docs.google.com/document/d/18TqaKGaSkjjAveE4lgoSjUjbBMRUCBwkaIG7fswYstc/edit?tab=t.0#heading=h.eygte8x8771t';
        $data = $parser->parseDocument($url);

        $structure = $parser->parseDocument($url)['structure'];
        dd($structure);

        $parser->iterateContent($structure, function($item, $depth) {
            $indent = str_repeat('  ', $depth);
            echo "{$indent}{$item['type']}: {$item['title']}\n";

            // Client can decide what to print at each level
            if ($item['type'] === 'sala' && $depth === 0) {
                echo "{$indent}[PRINT SALA LABEL]\n";
            }
        });
        dd($data);
        $documentId = $this->extractDocumentId($documentUrl);
        $client = $this->getClient();
        $service = new Google_Service_Docs($client);


        $doc = $service->documents->get($documentId);
        dd($doc);


    }
}
