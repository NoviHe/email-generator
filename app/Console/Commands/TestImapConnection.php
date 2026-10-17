<?php

namespace App\Console\Commands;

use App\Services\ImapService;
use Illuminate\Console\Command;

class TestImapConnection extends Command
{
    protected $signature = 'emails:test-connection';
    protected $description = 'Test connection to the cPanel IMAP server';

    public function handle(ImapService $imapService): int
    {
        $this->info('Testing IMAP connection to ' . config('imap.host') . ':' . config('imap.port') . '...');
        $this->line('Username: ' . config('imap.username'));
        $this->line('Encryption: ' . config('imap.encryption'));

        $result = $imapService->testConnection();

        if ($result['success']) {
            $this->info('✅ IMAP Connection SUCCESSFUL!');
            $this->line('Mailbox: ' . ($result['mailbox'] ?? 'INBOX'));
            $this->line('Total Messages: ' . ($result['messages'] ?? 0));
            $this->line('Recent Messages: ' . ($result['recent'] ?? 0));
            return Command::SUCCESS;
        } else {
            $this->error('❌ IMAP Connection FAILED!');
            $this->error('Error details: ' . ($result['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }
}
