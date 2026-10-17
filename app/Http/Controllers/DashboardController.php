<?php

namespace App\Http\Controllers;

use App\Models\GeneratedEmail;
use App\Services\EmailGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
