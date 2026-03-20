<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmOwner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'farm_name', 'farm_address', 'city', 'province', 'postal_code',
        'latitude', 'longitude', 'business_registration_number', 'valid_id_path',
        'permit_status', 'permit_expiry_date', 'subscription_status', 'monthly_revenue',
        'total_products', 'total_orders', 'average_rating'
    ];

    protected $casts = [
        'permit_expiry_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'monthly_revenue' => 'decimal:2',
        'average_rating' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    // New Module Relationships
    public function flocks()
    {
        return $this->hasMany(Flock::class);
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function supplyItems()
    {
        return $this->hasMany(SupplyItem::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function incomeRecords()
    {
        return $this->hasMany(IncomeRecord::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    // Query Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('subscription_status', 'active');
    }

    public function scopePermitApproved(Builder $query)
    {
        return $query->where('permit_status', 'approved');
    }

    public function scopeTopRated(Builder $query)
    {
        return $query->orderByDesc('average_rating');
    }

    public function scopeWithSubscription(Builder $query)
    {
        return $query->with('subscription');
    }

    public function scopeWithActiveProducts(Builder $query)
    {
        return $query->with(['products' => fn($q) => $q->where('status', 'active')]);
    }

    public function scopeByCity(Builder $query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByProvince(Builder $query, string $province)
    {
        return $query->where('province', $province);
    }

    public function scopeWithinDistance(Builder $query, float $lat, float $lng, float $distanceKm = 50)
    {
        return $query->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?',
            [$lat, $lng, $lat, $distanceKm]
        );
    }

    public function scopeWithStats(Builder $query)
    {
        return $query->selectRaw('*, COALESCE(average_rating, 0) as rating_score');
    }

    /**
     * Return a browser-safe URL for the uploaded valid ID.
     */
    public function getValidIdUrlAttribute(): ?string
    {
        if (!$this->valid_id_path) {
            return null;
        }

        $path = str_replace('\\\\', '/', trim($this->valid_id_path));

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        return Storage::disk('public')->url($path);
    }
}
