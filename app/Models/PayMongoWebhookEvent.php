<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayMongoWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'paymongo_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'status',
        'response_code',
        'payload',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
