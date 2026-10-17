<?php

namespace App\Services;

use App\Models\GeneratedEmail;
use App\Models\ReceivedEmail;
use Illuminate\Support\Facades\Log;

class ImapService
{
    private $connection = null;
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host = config('imap.host');
        $this->port = config('imap.port');
        $this->encryption = config('imap.encryption');
        $this->username = config('imap.username');
        $this->password = config('imap.password');
    }

    public function connect(): bool
    {
        @imap_timeout(IMAP_OPENTIMEOUT, 10);
        @imap_timeout(IMAP_READTIMEOUT, 15);

        $enc = strtolower($this->encryption);
        if ($enc === 'ssl') {
            $flags = "/imap/ssl/novalidate-cert";
        } elseif ($enc === 'tls') {
            $flags = "/imap/tls/novalidate-cert";
        } else {
            $flags = "/imap/notls";
        }

        $mailbox = "{" . $this->host . ":" . $this->port . $flags . "}INBOX";

        try {
            $this->connection = @imap_open($mailbox, $this->username, $this->password);

            if (!$this->connection) {
                Log::error('IMAP connection failed: ' . imap_last_error());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('IMAP connection error: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            if (config('imap.delete_after_fetch', false)) {
                @imap_expunge($this->connection);
            }
            @imap_close($this->connection);
            $this->connection = null;
        }
    }

    public function fetchNewEmails(): array
    {
        if (!$this->connection) {
            if (!$this->connect()) {
                return [];
            }
        }

        $fetched = [];

        try {
            // Search for unseen emails
            $emails = @imap_search($this->connection, 'UNSEEN');

            if (!$emails) {
                return [];
            }

            // Get all active generated email addresses
            $activeEmails = GeneratedEmail::active()
                ->pluck('id', 'email_address')
                ->toArray();

            foreach ($emails as $emailNumber) {
                $header = @imap_headerinfo($this->connection, $emailNumber);
                $structure = @imap_fetchstructure($this->connection, $emailNumber);

                if (!$header) {
                    continue;
                }

                // Get all possible recipient addresses (To, CC, Delivered-To, Envelope-To)
                $toAddresses = [];
                if (isset($header->to)) {
                    foreach ($header->to as $to) {
                        $toAddresses[] = strtolower($to->mailbox . '@' . $to->host);
                    }
                }

                // Check CC as well
                if (isset($header->cc)) {
                    foreach ($header->cc as $cc) {
                        $toAddresses[] = strtolower($cc->mailbox . '@' . $cc->host);
                    }
                }

                // Check raw headers for Delivered-To / Envelope-To (common in cPanel catch-all)
                $rawHeaders = @imap_fetchheader($this->connection, $emailNumber);
                if ($rawHeaders) {
                    if (preg_match_all('/(?:Delivered-To|Envelope-To|X-Delivered-To|X-Original-To):\s*<?([^>\r\n]+)>?/i', $rawHeaders, $matches)) {
                        foreach ($matches[1] as $addr) {
                            $toAddresses[] = strtolower(trim($addr));
                        }
                    }
                }

                $toAddresses = array_unique($toAddresses);

                // Match with active generated emails
                foreach ($toAddresses as $toAddress) {
                    if (isset($activeEmails[$toAddress])) {
                        $generatedEmailId = $activeEmails[$toAddress];

                        // Get message ID
                        $messageId = isset($header->message_id) ? $header->message_id : null;

                        // Skip if already fetched
                        if ($messageId && ReceivedEmail::where('message_id', $messageId)->exists()) {
                            continue;
                        }

                        // Get body
                        $body = $this->getBody($emailNumber, $structure);

                        // Get from info
                        $fromAddress = '';
                        $fromName = '';
                        if (isset($header->from[0])) {
                            $fromAddress = $header->from[0]->mailbox . '@' . $header->from[0]->host;
                            $fromName = isset($header->from[0]->personal) ? imap_utf8($header->from[0]->personal) : '';
                        }

                        // Get subject
                        $subject = isset($header->subject) ? imap_utf8($header->subject) : '(No Subject)';

                        // Get date
                        $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now();

                        $received = ReceivedEmail::create([
                            'generated_email_id' => $generatedEmailId,
                            'message_id' => $messageId,
                            'from_address' => $fromAddress,
                            'from_name' => $fromName,
                            'subject' => $subject,
                            'body_html' => $body['html'],
                            'body_text' => $body['text'],
                            'received_at' => $date,
                            'is_read' => false,
                        ]);

                        $fetched[] = $received;
                    }
                }

                // If delete_after_fetch is enabled, delete from cPanel mailbox to keep quota 0%
                if (config('imap.delete_after_fetch', true)) {
                    @imap_delete($this->connection, (int) $emailNumber);
                } else {
                    @imap_setflag_full($this->connection, (string) $emailNumber, '\\Seen');
                }
            }
        } catch (\Exception $e) {
            Log::error('IMAP fetch error: ' . $e->getMessage());
        }

        return $fetched;
    }

    private function getBody(int $emailNumber, object $structure): array
    {
        $html = '';
        $text = '';

        if (isset($structure->parts) && count($structure->parts) > 0) {
            foreach ($structure->parts as $partIndex => $part) {
                $body = imap_fetchbody($this->connection, $emailNumber, (string) ($partIndex + 1));
                $body = $this->decodeBody($body, $part->encoding ?? 0);

                if ($part->subtype === 'HTML') {
                    $html = $body;
                } elseif ($part->subtype === 'PLAIN') {
                    $text = $body;
                }

                // Check nested parts (multipart/alternative within multipart/mixed)
                if (isset($part->parts)) {
                    foreach ($part->parts as $subIndex => $subPart) {
                        $subBody = imap_fetchbody($this->connection, $emailNumber, ($partIndex + 1) . '.' . ($subIndex + 1));
                        $subBody = $this->decodeBody($subBody, $subPart->encoding ?? 0);

                        if ($subPart->subtype === 'HTML') {
                            $html = $subBody;
                        } elseif ($subPart->subtype === 'PLAIN') {
                            $text = $subBody;
                        }
                    }
                }
            }
        } else {
            // Simple email (no parts)
            $body = imap_body($this->connection, $emailNumber);
            $body = $this->decodeBody($body, $structure->encoding ?? 0);

            if (isset($structure->subtype) && $structure->subtype === 'HTML') {
                $html = $body;
            } else {
                $text = $body;
            }
        }

        // Convert charset if needed
        if ($html) {
            $html = $this->convertCharset($html, $structure);
        }
        if ($text) {
            $text = $this->convertCharset($text, $structure);
        }

        return ['html' => $html, 'text' => $text];
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($body),      // BASE64
            4 => quoted_printable_decode($body), // QUOTED-PRINTABLE
            default => $body,
        };
    }

    private function convertCharset(string $text, object $structure): string
    {
        $charset = 'UTF-8';

        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtoupper($param->attribute) === 'CHARSET') {
                    $charset = strtoupper($param->value);
                }
            }
        }

        if ($charset !== 'UTF-8' && $charset !== 'US-ASCII') {
            $converted = @iconv($charset, 'UTF-8//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
    }

    public function testConnection(): array
    {
        if ($this->connect()) {
            $check = imap_check($this->connection);
            $result = [
                'success' => true,
                'mailbox' => $check->Mailbox ?? 'unknown',
                'messages' => $check->Nmsgs ?? 0,
                'recent' => $check->Recent ?? 0,
            ];
            $this->disconnect();
            return $result;
        }

        return [
            'success' => false,
            'error' => imap_last_error() ?: 'Unknown error',
        ];
    }
}
