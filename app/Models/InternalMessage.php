<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_owner_id',
        'sender_id',
        'sender_role',
        'recipient_role',
        'message_type',
        'subject',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function farmOwner(): BelongsTo
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
