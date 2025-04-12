<?php
namespace App\Service;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Drive;

class GoogleClientService
{
    public function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets + Drive Integration');
        $client->setAuthConfig($_SERVER['DOCUMENT_ROOT']. ".." . '/google.json'); // Use app root path
        $client->setScopes([
            \Google_Service_Sheets::SPREADSHEETS_READONLY,
            \Google_Service_Drive::DRIVE_READONLY
        ]);
        $client->setAccessType('offline');
        return $client;
    }
}