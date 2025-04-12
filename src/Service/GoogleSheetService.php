<?php
namespace App\Service;

use Google_Service_Sheets;

class GoogleSheetService
{
    public function __construct(private GoogleClientService $googleClientService) {}

    public function getPhotoUrls(string $spreadsheetId, string $range): array
    {
        $client = $this->googleClientService->getClient();
        $service = new Google_Service_Sheets($client);

        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues();

        //return photo URLs from column 3
        $photoUrls = [];
        foreach ($rows as $row) {
            if (isset($row[2])) {
                $photoUrls[] = $row[2];
            }
        }
        return $photoUrls;
    }
}