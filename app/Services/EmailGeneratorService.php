<?php

namespace App\Services;

use App\Models\GeneratedEmail;
use App\Models\User;
use Illuminate\Support\Str;

class EmailGeneratorService
{
    public function generate(User $user): GeneratedEmail
    {
        $domain = config('imap.domain');
        $maxEmails = config('imap.email_max_per_user');
        $expireMinutes = config('imap.email_expire_minutes');

        // Check if user has reached the limit (0 or null means unlimited)
        if ($maxEmails && $maxEmails > 0) {
            $activeCount = $user->generatedEmails()->active()->count();
            if ($activeCount >= $maxEmails) {
                throw new \Exception("Maximum of {$maxEmails} active emails reached. Please delete or wait for existing ones to expire.");
            }
        }

        // Generate unique local part
        $localPart = $this->generateLocalPart();
        $emailAddress = $localPart . '@' . $domain;

        // Ensure uniqueness
        while (GeneratedEmail::where('email_address', $emailAddress)->exists()) {
            $localPart = $this->generateLocalPart();
            $emailAddress = $localPart . '@' . $domain;
        }

        return GeneratedEmail::create([
            'user_id' => $user->id,
            'email_address' => $emailAddress,
            'local_part' => $localPart,
            'is_active' => true,
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);
    }

    private function generateLocalPart(): string
    {
        $adjectives = ['cool', 'fast', 'blue', 'red', 'green', 'wild', 'calm', 'bold', 'dark', 'gold', 'iron', 'jade', 'keen', 'nova', 'pure', 'sage', 'true', 'warm', 'zen', 'epic'];
        $nouns = ['tiger', 'eagle', 'wolf', 'hawk', 'bear', 'lion', 'fox', 'deer', 'owl', 'cat', 'star', 'moon', 'sun', 'wave', 'wind', 'fire', 'ice', 'rock', 'leaf', 'bolt'];

        $adj = $adjectives[array_rand($adjectives)];
        $noun = $nouns[array_rand($nouns)];
        $number = rand(100, 9999);

        return "{$adj}.{$noun}{$number}";
    }

    public function deactivate(GeneratedEmail $email): void
    {
        $email->update(['is_active' => false]);
    }

    public function cleanupExpired(): int
    {
        $expired = GeneratedEmail::where('is_active', true)
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $email) {
            $email->update(['is_active' => false]);
        }

        return $expired->count();
    }
}
