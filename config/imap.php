<?php

return [
    'host' => env('IMAP_HOST', 'localhost'),
    'port' => env('IMAP_PORT', 993),
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    'username' => env('IMAP_USERNAME', ''),
    'password' => env('IMAP_PASSWORD', ''),
    'domain' => env('IMAP_DOMAIN', 'example.com'),
    'email_expire_minutes' => env('EMAIL_EXPIRE_MINUTES', 0), // 0 = permanent (never expires)
    'email_max_per_user' => env('EMAIL_MAX_PER_USER', 0), // 0 = unlimited
    'delete_after_fetch' => env('IMAP_DELETE_AFTER_FETCH', true), // auto-delete from cPanel mailbox after imported to DB
];
