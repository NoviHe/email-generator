<?php

namespace App\Http\Controllers;

use App\Models\GeneratedEmail;
use App\Services\EmailGeneratorService;
use App\Services\ImapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, ImapService $imapService)
    {
        // Auto-sync IMAP when viewing or reloading dashboard (throttled to at most once per 4 seconds)
        try {
            $configured = config('imap.username') && config('imap.password') && config('imap.host') !== 'localhost';
            if ($configured && Cache::add('imap_sync_lock', true, 4)) {
                $imapService->fetchNewEmails();
                $imapService->disconnect();
            }
        } catch (\Throwable $e) {
            // Ignore connection errors
        }

        $emails = $request->user()
            ->generatedEmails()
            ->active()
            ->withCount(['receivedEmails', 'receivedEmails as unread_count' => function ($query) {
                $query->where('is_read', false);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Dashboard', [
            'generatedEmails' => $emails,
            'maxEmails' => config('imap.email_max_per_user'),
            'domain' => config('imap.domain'),
        ]);
    }
}
