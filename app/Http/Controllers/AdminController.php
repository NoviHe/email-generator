<?php

namespace App\Http\Controllers;

use App\Models\GeneratedEmail;
use App\Models\ReceivedEmail;
use App\Models\User;
use App\Services\EmailGeneratorService;
use App\Services\ImapService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // 1. System Statistics
        $stats = [
            'total_users' => User::count(),
            'total_generated_emails' => GeneratedEmail::count(),
            'active_generated_emails' => GeneratedEmail::active()->count(),
            'expired_generated_emails' => GeneratedEmail::where('is_active', false)
                ->orWhere(function ($q) {
                    $q->whereNotNull('expires_at')->where('expires_at', '<=', now());
                })->count(),
            'total_received_emails' => ReceivedEmail::count(),
            'received_today' => ReceivedEmail::whereDate('received_at', today())->count(),
        ];

        // 2. Users List (paginated or with counts)
        $users = User::withCount(['generatedEmails', 'generatedEmails as active_emails_count' => function ($q) {
                $q->active();
            }])
            ->with(['generatedEmails' => function ($q) {
                $q->withCount('receivedEmails');
            }])
            ->latest()
            ->paginate(15, ['*'], 'users_page');

        // 3. All Generated Emails across all users
        $generatedEmails = GeneratedEmail::with(['user:id,name,email'])
            ->withCount(['receivedEmails', 'receivedEmails as unread_count' => function ($q) {
                $q->where('is_read', false);
            }])
            ->latest()
            ->paginate(15, ['*'], 'emails_page');

        // 4. All Received Emails across all generated emails
        $receivedEmails = ReceivedEmail::with(['generatedEmail.user:id,name,email'])
            ->latest('received_at')
            ->paginate(15, ['*'], 'logs_page');

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'users' => $users,
            'generatedEmails' => $generatedEmails,
            'receivedEmails' => $receivedEmails,
            'imapConfig' => [
                'host' => config('imap.host'),
                'username' => config('imap.username'),
                'domain' => config('imap.domain'),
                'port' => config('imap.port'),
            ],
        ]);
    }

    public function toggleAdmin(User $user, Request $request)
    {
        // Prevent super admin from demoting themselves
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own admin role.');
        }

        $user->update(['is_admin' => !$user->is_admin]);

        $status = $user->is_admin ? 'promoted to Admin' : 'demoted to Regular User';
        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function deleteUser(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account from admin panel.');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "User {$userName} and all associated emails have been deleted.");
    }

    public function deleteGeneratedEmail(GeneratedEmail $generatedEmail)
    {
        $address = $generatedEmail->email_address;
        $generatedEmail->delete();

        return back()->with('success', "Email address {$address} deleted.");
    }

    public function deleteReceivedEmail(ReceivedEmail $receivedEmail)
    {
        $receivedEmail->delete();

        return back()->with('success', 'Received email record deleted.');
    }

    public function syncImap(ImapService $imapService)
    {
        try {
            $fetched = $imapService->fetchNewEmails();
            $imapService->disconnect();
            $count = count($fetched);

            return back()->with('success', "IMAP Sync complete! {$count} new email(s) fetched.");
        } catch (\Throwable $e) {
            return back()->with('error', 'IMAP Sync failed: ' . $e->getMessage());
        }
    }

    public function cleanupExpired(EmailGeneratorService $service)
    {
        $count = $service->cleanupExpired();

        return back()->with('success', "Cleanup complete! {$count} expired email(s) deactivated.");
    }
}
