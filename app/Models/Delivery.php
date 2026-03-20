<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_owner_id', 'order_id', 'driver_id', 'assigned_by', 'tracking_number',
        'recipient_name', 'recipient_phone', 'delivery_address', 'city', 'province',
        'postal_code', 'latitude', 'longitude', 'scheduled_date', 'scheduled_time_from',
        'scheduled_time_to', 'dispatched_at', 'delivered_at', 'status', 'failure_reason',
        'delivery_fee', 'cod_amount', 'cod_collected', 'proof_of_delivery_url',
        'delivery_notes', 'special_instructions', 'delivery_attempts', 'rating', 'feedback'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time_from' => 'datetime:H:i',
        'scheduled_time_to' => 'datetime:H:i',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'cod_collected' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
    ];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Query Scopes
    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeByDriver(Builder $query, int $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeByStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query)
    {
        return $query->whereIn('status', ['preparing', 'packed', 'assigned']);
    }

    public function scopeInTransit(Builder $query)
    {
        return $query->where('status', 'out_for_delivery');
    }

    public function scopeDelivered(Builder $query)
    {
        return $query->whereIn('status', ['delivered', 'completed']);
    }

    public function scopeFailed(Builder $query)
    {
        return $query->whereIn('status', ['failed', 'returned']);
    }

    public function scopeScheduledToday(Builder $query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    public function scopeUnassigned(Builder $query)
    {
        return $query->whereNull('driver_id')->where('status', 'packed');
    }

    // Methods
    public function assignDriver(int $driverId, int $userId): void
    {
        $this->update([
            'driver_id' => $driverId,
            'assigned_by' => $userId,
            'status' => 'assigned'
        ]);
    }

    public function markPacked(): void
    {
        $this->update(['status' => 'packed']);
    }

    public function dispatch(): void
    {
        $this->update([
            'status' => 'out_for_delivery',
            'dispatched_at' => now()
        ]);

        if ($this->driver) {
            $this->driver->markOnDelivery();
        }
    }

    public function markInTransit(): void
    {
        $this->update(['status' => 'out_for_delivery']);
    }

    public function markDelivered(?string $proofUrl = null): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'proof_of_delivery_url' => $proofUrl
        ]);

        if ($this->driver) {
            $this->driver->completeDelivery($this->delivery_fee);
        }
    }

    public function markFailed(string $reason): void
    {
        $this->increment('delivery_attempts');
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason
        ]);

        if ($this->driver) {
            $this->driver->markAvailable();
        }
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function rateDelivery(float $rating, ?string $feedback = null): void
    {
        $this->update([
            'rating' => $rating,
            'feedback' => $feedback
        ]);

        if ($this->driver) {
            $this->driver->updateRating($rating);
        }
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delivery) {
            if (!$delivery->tracking_number) {
                $delivery->tracking_number = 'TRK-' . strtoupper(uniqid());
            }

            if (empty($delivery->status)) {
                $delivery->status = 'preparing';
            }
        });
    }
}
