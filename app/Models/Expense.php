<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_owner_id', 'recorded_by', 'approved_by', 'supplier_id', 'expense_number',
        'source_type', 'source_id', 'is_auto_generated',
        'category', 'subcategory', 'description', 'amount', 'tax_amount', 'total_amount',
        'expense_date', 'due_date', 'payment_status', 'payment_method', 'reference_number',
        'receipt_url', 'status', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expense_date' => 'date',
        'due_date' => 'date',
        'is_auto_generated' => 'boolean',
    ];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Query Scopes
    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeByCategory(Builder $query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopePending(Builder $query)
    {
        return $query->whereIn('payment_status', ['pending', 'partial']);
    }

    public function scopePaid(Builder $query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeOverdue(Builder $query)
    {
        return $query->where('payment_status', '!=', 'paid')
                     ->where('due_date', '<', today());
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereMonth('expense_date', now()->month)
                     ->whereYear('expense_date', now()->year);
    }

    // Methods
    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId
        ]);
    }

    public function markPaid(string $method, ?string $reference = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_method' => $method,
            'reference_number' => $reference
        ]);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (!$expense->expense_number) {
                $expense->expense_number = 'EXP-' . date('Ymd') . '-' . str_pad(static::count() + 1, 5, '0', STR_PAD_LEFT);
            }
            $expense->total_amount = $expense->amount + ($expense->tax_amount ?? 0);
        });
    }
}
