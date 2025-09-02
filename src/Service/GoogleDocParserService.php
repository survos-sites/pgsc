<?php
namespace App\Service;

use Google\Service\Docs\Paragraph;
use Google_Client;
use Google_Service_Docs;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GoogleDocParserService
{
    public function __construct(
        #[Autowire('%env(JSON_AUTH)%')]
        private readonly string $jsonAuth
    ) {}

    public function parseDocument(string $documentUrl): array
    {
        $documentId = $this->extractDocumentId($documentUrl);
        $client = $this->getClient();
        $service = new Google_Service_Docs($client);
        $doc = $service->documents->get($documentId);

        return [
            'metadata' => [
                'title' => $doc->getTitle(),
                'id' => $documentId,
                'revision' => $doc->getRevisionId(),
            ],
            'structure' => $this->parseStructure($doc->getBody()->getContent())
        ];
    }

    private function parseStructure(array $content): array
    {
        $parsed = [];
        $stack = []; // Stack of references to current hierarchy

        foreach ($content as $element) {
            if (!isset($element['paragraph'])) continue;

            $paragraph = $element['paragraph'];
            $style = $paragraph['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT';
            $text = $this->extractText($paragraph);

            if (empty(trim($text))) continue;

            $level = $this->getHierarchyLevel($style, $text);

            $item = [
                'type' => $this->determineContentType($style, $text),
                'level' => $level,
                'title' => trim($text),
                'location_code' => $this->parseLocationCode($text),
                'content_hash' => md5($text),
                'children' => []
            ];

            if ($level === null) {
                // Regular content - add to current parent
                if (!empty($stack)) {
                    $stack[count($stack) - 1]['children'][] = $item;
                } else {
                    $parsed[] = $item;
                }
            } else {
                // Hierarchy marker
                $stack = array_slice($stack, 0, $level); // Trim stack to current level

                if ($level === 0) {
                    // Root level
                    $parsed[] = $item;
                    $stack = [&$parsed[count($parsed) - 1]]; // Reference to last added item
                } else {
                    // Child level
                    if (!empty($stack)) {
                        $parent = &$stack[count($stack) - 1];
                        $parent['children'][] = $item;
                        $stack[] = &$parent['children'][count($parent['children']) - 1];
                    } else {
                        // Orphaned - add to root
                        $parsed[] = $item;
                        $stack = [&$parsed[count($parsed) - 1]];
                    }
                }
            }
        }

        return $parsed;
    }

    public function iterateContent(array $items, callable $callback, int $depth = 0): void
    {
        foreach ($items as $item) {
            $callback($item, $depth);

            if (!empty($item['children'])) {
                $this->iterateContent($item['children'], $callback, $depth + 1);
            }
        }
    }

    private function extractText(Paragraph $paragraph): string
    {
        $text = '';
        $elements = $paragraph['elements'] ?? [];

        foreach ($elements as $element) {
            if (isset($element['textRun']['content'])) {
                $text .= $element['textRun']['content'];
            }
        }

        return $text;
    }

    private function getHierarchyLevel(string $style, string $text): ?int
    {
        // Check for location codes first
        if (preg_match('/^(SALA-\d+):/', $text)) {
            return 0; // Root level
        }
        if (preg_match('/^(VIT-\d+-\d+):/', $text)) {
            return 1; // Vitrina level
        }
        if (preg_match('/^(POS-\d+-\d+-\d+):/', $text)) {
            return 2; // Position level
        }

        // Fall back to heading styles
        switch ($style) {
            case 'HEADING_1':
                return 0;
            case 'HEADING_2':
                return 0; // SALA level
            case 'HEADING_3':
                return 1; // Section level
            case 'HEADING_4':
                return 2; // Vitrina level
            case 'HEADING_5':
                return 3; // Position level
            default:
                return null; // Regular content, not a hierarchy marker
        }
    }

    private function determineContentType(string $style, string $text): string
    {
        // Check location codes first
        if (preg_match('/^SALA-\d+:/', $text)) {
            return 'sala';
        }
        if (preg_match('/^VIT-\d+-\d+:/', $text)) {
            return 'vitrina';
        }
        if (preg_match('/^POS-\d+-\d+-\d+:/', $text)) {
            return 'ficha_descriptiva';
        }

        // Check for numbered sections (like "1. Title", "2. Title")
        if (preg_match('/^\d+\.\s/', $text)) {
            return 'dialogo';
        }

        // Fall back to heading styles
        switch ($style) {
            case 'HEADING_1':
            case 'HEADING_2':
                return 'sala';
            case 'HEADING_3':
                return 'dialogo';
            case 'HEADING_4':
                return 'vitrina';
            case 'HEADING_5':
                return 'ficha_descriptiva';
            case 'HEADING_6':
                return 'ficha_tecnica';
            default:
                return 'paragraph';
        }
    }

    private function parseLocationCode(string $text): ?string
    {
        if (preg_match('/^(SALA-\d+|VIT-\d+-\d+|POS-\d+-\d+-\d+):/', $text, $matches)) {
            return $matches[1];
        }
        return null;
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