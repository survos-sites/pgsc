<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\SyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;

#[AsCommand('app:sync', 'Sync artists and locations from Google Spreadsheet to database')]
class SyncCommand extends Command
{
    public function __construct(
        private readonly SyncService $syncService,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Force re-fetch from Google (bypass cache)')] bool $refresh = false,
    ): int {
        if (empty($this->syncService->getSpreadsheetId())) {
            $io->error('GOOGLE_SPREADSHEET_ID is not configured.');
            return Command::FAILURE;
        }

        $io->title('Google Spreadsheet → Database Sync');

        $counts = $this->syncService->sync($refresh);

        $io->success(sprintf(
            'Sync complete. Artists: %d  |  Locations: %d  |  Obras: %d  |  Sheets skipped: %d',
            $counts['artists'],
            $counts['locations'],
            $counts['obras'],
            count($counts['skipped']),
        ));

        if (!empty($counts['warnings'])) {
            $io->warning(sprintf('%d warning(s):', count($counts['warnings'])));
            $rows = array_map(
                fn(array $w) => [$w['sheet'], $w['row'] ?? '-', $w['obra'] ?? '-', $w['message']],
                $counts['warnings']
            );
            $io->table(['Sheet', 'Row', 'Obra', 'Message'], $rows);
        }

        return Command::SUCCESS;
    }
}
