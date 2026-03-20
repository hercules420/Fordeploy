<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEditRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_owner_id',
        'payroll_id',
        'requested_by',
        'reviewed_by',
        'status',
        'reason',
        'requested_changes',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_changes' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function farmOwner(): BelongsTo
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
