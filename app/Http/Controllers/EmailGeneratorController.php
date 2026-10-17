<?php

namespace App\Http\Controllers;

use App\Models\GeneratedEmail;
use App\Services\EmailGeneratorService;
use Illuminate\Http\Request;

class EmailGeneratorController extends Controller
{
    private EmailGeneratorService $service;

    public function __construct(EmailGeneratorService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        try {
            $email = $this->service->generate($request->user());

            return back()->with('success', 'Email address generated: ' . $email->email_address);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(GeneratedEmail $generatedEmail)
    {
        // Ensure the user owns this email
        if ($generatedEmail->user_id !== auth()->id()) {
            abort(403);
        }

        $this->service->deactivate($generatedEmail);

        return back()->with('success', 'Email address deactivated.');
    }
}
