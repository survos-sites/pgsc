<?php
namespace App\Service;

use Google_Service_Drive;

class GoogleDriveService
{
    public function __construct(private GoogleClientService $googleClientService) {}

    public function downloadFileFromUrl(string $driveUrl, string $destinationPath): void
    {
        $fileId = $this->extractFileIdFromUrl($driveUrl);
        if (!$fileId) {
            throw new \Exception("Invalid Drive URL");
        }

        $client = $this->googleClientService->getClient();
        $service = new Google_Service_Drive($client);

        $response = $service->files->get($fileId, [
            'alt' => 'media'
        ]);

        $content = $response->getBody()->getContents();
        file_put_contents($destinationPath, $content);
    }

    private function extractFileIdFromUrl(string $url): ?string
    {
        if (preg_match('/\/file\/d\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/id=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}