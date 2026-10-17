<?php

namespace App\Console\Commands;

use App\Models\GeneratedEmail;
use App\Models\ReceivedEmail;
use Illuminate\Console\Command;

class SimulateIncomingEmail extends Command
{
    protected $signature = 'emails:simulate {email? : The generated email address to send to}';
    protected $description = 'Simulate an incoming verification email from social media (Facebook, Instagram, Google, etc.)';

    public function handle(): int
    {
        $emailAddress = $this->argument('email');

        if ($emailAddress) {
            $generatedEmail = GeneratedEmail::where('email_address', $emailAddress)->first();
        } else {
            $generatedEmail = GeneratedEmail::active()->latest()->first();
        }

        if (!$generatedEmail) {
            $this->error('No active generated email found. Please generate an email in the dashboard first.');
            return Command::FAILURE;
        }

        $code = rand(100000, 999999);
        $services = [
            [
                'from_name' => 'Facebook Security',
                'from_address' => 'security@facebookmail.com',
                'subject' => "{$code} adalah kode konfirmasi akun Facebook Anda",
                'body_html' => "<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e1e4e8; border-radius: 8px;\">
                    <h2 style=\"color: #1877f2;\">Facebook</h2>
                    <p style=\"font-size: 16px; color: #333;\">Halo,</p>
                    <p style=\"font-size: 15px; color: #555;\">Kami menerima permintaan untuk memverifikasi akun Anda. Masukkan kode konfirmasi berikut:</p>
                    <div style=\"background: #f0f2f5; padding: 15px; border-radius: 6px; text-align: center; margin: 20px 0;\">
                        <span style=\"font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #1877f2;\">{$code}</span>
                    </div>
                    <p style=\"font-size: 13px; color: #888;\">Kode ini berlaku selama 15 menit. Jika Anda tidak meminta kode ini, abaikan email ini.</p>
                </div>",
                'body_text' => "Facebook\n\nHalo,\n\nKode konfirmasi akun Facebook Anda adalah: {$code}\n\nKode ini berlaku selama 15 menit.",
            ],
            [
                'from_name' => 'Instagram',
                'from_address' => 'no-reply@mail.instagram.com',
                'subject' => "{$code} adalah kode keamanan Instagram Anda",
                'body_html' => "<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e1e4e8; border-radius: 8px;\">
                    <h2 style=\"background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); -webkit-background-clip: text; color: #cc2366;\">Instagram</h2>
                    <p style=\"font-size: 16px; color: #333;\">Hai,</p>
                    <p style=\"font-size: 15px; color: #555;\">Seseorang mencoba mengakses akun Anda. Masukkan kode berikut untuk melanjutkan:</p>
                    <div style=\"background: #fafafa; border: 1px dashed #cc2366; padding: 15px; border-radius: 6px; text-align: center; margin: 20px 0;\">
                        <span style=\"font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #262626;\">{$code}</span>
                    </div>
                    <p style=\"font-size: 13px; color: #888;\">Keamanan akun Anda adalah prioritas kami.</p>
                </div>",
                'body_text' => "Instagram\n\nHai,\n\nKode keamanan Instagram Anda: {$code}\n\nMasukkan kode ini untuk memverifikasi akun Anda.",
            ],
            [
                'from_name' => 'Google Verification',
                'from_address' => 'noreply@google.com',
                'subject' => "Kode Verifikasi Google Anda: G-{$code}",
                'body_html' => "<div style=\"font-family: Roboto, Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #dadce0; border-radius: 8px;\">
                    <h2 style=\"color: #4285f4;\">Google</h2>
                    <p style=\"font-size: 16px; color: #202124;\">Verifikasi Akun</p>
                    <p style=\"font-size: 14px; color: #5f6368;\">Gunakan kode verifikasi berikut untuk menyelesaikan pendaftaran:</p>
                    <div style=\"margin: 25px 0;\">
                        <span style=\"font-size: 28px; font-weight: 500; letter-spacing: 3px; color: #202124; background: #e8f0fe; padding: 10px 20px; border-radius: 4px;\">G-{$code}</span>
                    </div>
                    <p style=\"font-size: 12px; color: #70757a;\">Jangan pernah membagikan kode ini kepada siapa pun.</p>
                </div>",
                'body_text' => "Google\n\nKode Verifikasi Google Anda: G-{$code}\n\nJangan pernah membagikan kode ini kepada siapa pun.",
            ],
        ];

        $template = $services[array_rand($services)];

        $email = ReceivedEmail::create([
            'generated_email_id' => $generatedEmail->id,
            'message_id' => 'sim_' . uniqid() . '@simulated',
            'from_address' => $template['from_address'],
            'from_name' => $template['from_name'],
            'subject' => $template['subject'],
            'body_html' => $template['body_html'],
            'body_text' => $template['body_text'],
            'received_at' => now(),
            'is_read' => false,
        ]);

        $this->info("Simulated email sent to: {$generatedEmail->email_address}");
        $this->info("From: {$template['from_name']} <{$template['from_address']}>");
        $this->info("Subject: {$template['subject']}");
        $this->info("Verification Code: {$code}");

        return Command::SUCCESS;
    }
}
