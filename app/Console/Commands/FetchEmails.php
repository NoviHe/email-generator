<?php

namespace App\Console\Commands;

use App\Services\ImapService;
use Illuminate\Console\Command;

class FetchEmails extends Command
{
    protected $signature = 'emails:fetch';
    protected $description = 'Fetch new emails from IMAP server';

    public function handle(ImapService $imapService): int
    {
        $this->info('Connecting to IMAP server...');

        if (!$imapService->connect()) {
            $this->error('Failed to connect to IMAP server.');
            return Command::FAILURE;
        }

        $this->info('Connected. Fetching new emails...');

        $fetched = $imapService->fetchNewEmails();

        $this->info('Fetched ' . count($fetched) . ' new email(s).');

        $imapService->disconnect();

        return Command::SUCCESS;
    }
}
