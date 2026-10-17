<?php

namespace App\Http\Controllers;

use App\Models\GeneratedEmail;
use App\Models\ReceivedEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Services\ImapService;
use Illuminate\Support\Facades\Cache;

class InboxController extends Controller
{
    public function index(GeneratedEmail $generatedEmail, Request $request, ImapService $imapService)
    {
        // Ensure the user owns this email
        if ($generatedEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        // Auto-sync IMAP when viewing or reloading inbox (throttled to at most once per 4 seconds)
        try {
            $configured = config('imap.username') && config('imap.password') && config('imap.host') !== 'localhost';
            if ($configured && Cache::add('imap_sync_lock', true, 4)) {
                $imapService->fetchNewEmails();
                $imapService->disconnect();
            }
        } catch (\Throwable $e) {
            // Ignore connection errors
        }

        $emails = $generatedEmail->receivedEmails()
            ->orderBy('received_at', 'desc')
            ->paginate(20);

        return Inertia::render('Inbox', [
            'generatedEmail' => $generatedEmail,
            'receivedEmails' => $emails,
        ]);
    }

    public function show(GeneratedEmail $generatedEmail, ReceivedEmail $receivedEmail, Request $request)
    {
        // Ensure the user owns this email
        if ($generatedEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        // Ensure the received email belongs to this generated email
        if ($receivedEmail->generated_email_id !== $generatedEmail->id) {
            abort(404);
        }

        // Mark as read
        if (!$receivedEmail->is_read) {
            $receivedEmail->update(['is_read' => true]);
        }

        return Inertia::render('EmailView', [
            'generatedEmail' => $generatedEmail,
            'email' => $receivedEmail,
        ]);
    }

    public function checkNew(GeneratedEmail $generatedEmail, Request $request, ImapService $imapService)
    {
        // Ensure the user owns this email
        if ($generatedEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        // Trigger on-demand IMAP check if configured (throttled to at most once per 6 seconds)
        try {
            $configured = config('imap.username') && config('imap.password') && config('imap.host') !== 'localhost';
            if ($configured && Cache::add('imap_sync_lock', true, 6)) {
                $imapService->fetchNewEmails();
                $imapService->disconnect();
            }
        } catch (\Throwable $e) {
            // Log or ignore connection issues during polling
        }

        $emails = $generatedEmail->receivedEmails()
            ->orderBy('received_at', 'desc')
            ->get();

        return response()->json([
            'emails' => $emails,
            'count' => $emails->count(),
        ]);
    }

    public function destroy(GeneratedEmail $generatedEmail, ReceivedEmail $receivedEmail, Request $request)
    {
        if ($generatedEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($receivedEmail->generated_email_id !== $generatedEmail->id) {
            abort(404);
        }

        $receivedEmail->delete();

        return back()->with('success', 'Email deleted.');
    }
}
