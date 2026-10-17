<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_email_id',
        'message_id',
        'from_address',
        'from_name',
        'subject',
        'body_html',
        'body_text',
        'received_at',
        'is_read',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function generatedEmail(): BelongsTo
    {
        return $this->belongsTo(GeneratedEmail::class);
    }
}
