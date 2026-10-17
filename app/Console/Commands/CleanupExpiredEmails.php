<?php

namespace App\Console\Commands;

use App\Services\EmailGeneratorService;
use Illuminate\Console\Command;

class CleanupExpiredEmails extends Command
{
    protected $signature = 'emails:cleanup';
    protected $description = 'Deactivate expired generated email addresses';

    public function handle(EmailGeneratorService $service): int
    {
        $count = $service->cleanupExpired();

        $this->info("Deactivated {$count} expired email(s).");

        return Command::SUCCESS;
    }
}
