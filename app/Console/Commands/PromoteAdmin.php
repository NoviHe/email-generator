<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdmin extends Command
{
    protected $signature = 'admin:promote {email : The email address of the user to promote}';
    protected $description = 'Promote a user to Super Admin';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return Command::FAILURE;
        }

        $user->update(['is_admin' => true]);

        $this->info("✅ User {$user->name} ({$user->email}) is now a Super Admin!");
        return Command::SUCCESS;
    }
}
