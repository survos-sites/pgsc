<?php

namespace App\Command;

use Google_Client;
use Google_Service_Docs;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:fetch-google-doc',
    description: 'Fetch and display Google Doc metadata'
)]
class FetchGoogleDocCommand extends Command
{
    public function __construct(
        #[Autowire('%env(JSON_AUTH)%')] 
        private readonly string $jsonAuth
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'document_url',
            mode: InputArgument::OPTIONAL,
            description: 'Google Docs URL or Document ID',
            default: 'https://docs.google.com/document/d/18TqaKGaSkjjAveE4lgoSjUjbBMRUCBwkaIG7fswYstc/'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $documentUrl = $input->getArgument('document_url');

        if (empty($this->jsonAuth)) {
            $io->error('Google JSON auth not configured. Set JSON_AUTH environment variable.');
            return Command::FAILURE;
        }

        try {
            $documentId = $this->extractDocumentId($documentUrl);
            $io->info("Fetching document ID: {$documentId}");

            $client = $this->getClient();
            $service = new Google_Service_Docs($client);

            $doc = $service->documents->get($documentId);
            
            $io->success('Document fetched successfully!');
            
            $io->section('Document Metadata:');
            $io->definitionList(
                ['Title' => $doc->getTitle()],
                ['Document ID' => $doc->getDocumentId()],
                ['Revision ID' => $doc->getRevisionId()],
                ['Content Elements' => count($doc->getBody()->getContent())]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to fetch document: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Museum Content Fetcher');
        
        $config = json_validate($this->jsonAuth) ? json_decode($this->jsonAuth, true) : $this->jsonAuth;
        $client->setAuthConfig($config);
        
        $client->setScopes([Google_Service_Docs::DOCUMENTS_READONLY]);
        $client->setAccessType('offline');
        
        return $client;
    }

    private function extractDocumentId(string $url): string
    {
        $patterns = [
            '/\/document\/d\/([a-zA-Z0-9-_]+)/',
            '/id=([a-zA-Z0-9-_]+)/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        if (preg_match('/^[a-zA-Z0-9-_]+$/', $url)) {
            return $url;
        }

        throw new \InvalidArgumentException('Could not extract document ID from URL');
    }
}
