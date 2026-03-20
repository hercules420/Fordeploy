<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_owner_id', 'sku', 'name', 'description', 'category', 'status',
        'quantity_available', 'quantity_sold', 'price', 'cost_price', 'attributes',
        'unit', 'minimum_order', 'is_bulk_order_enabled', 'order_quantity_step',
        'order_quantity_options',
        'discount_percentage', 'image_url', 'image_urls',
        'published_at'
        // view_count, favorite_count, average_rating, review_count are server-managed aggregates
        // and must never be set directly from user input.
    ];

    protected $casts = [
        'attributes' => 'json',
        'image_urls' => 'json',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'published_at' => 'datetime',
        'is_bulk_order_enabled' => 'boolean',
        'order_quantity_step' => 'integer',
        'order_quantity_options' => 'json',
    ];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Reviews relationship (uncomment when Review model is implemented)
    // public function reviews()
    // {
    //     return $this->morphMany(Review::class, 'reviewable');
    // }

    // Query Scopes - Performance Optimized
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active')->where('quantity_available', '>', 0);
    }

    public function scopeByCategory(Builder $query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByFarmOwner(Builder $query, int|FarmOwner $farmOwner)
    {
        $farmOwnerId = $farmOwner instanceof FarmOwner ? $farmOwner->id : $farmOwner;
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeAvailable(Builder $query)
    {
        return $query->where('status', 'active')->where('quantity_available', '>', 0);
    }

    public function scopePopular(Builder $query)
    {
        return $query->orderByDesc('view_count');
    }

    public function scopeTopRated(Builder $query)
    {
        return $query->whereNotNull('average_rating')->orderByDesc('average_rating');
    }

    public function scopeWithFarmOwner(Builder $query)
    {
        return $query->with('farmOwner:id,farm_name,average_rating');
    }

    public function scopeSearchByName(Builder $query, string $search)
    {
        return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
    }

    public function scopeByPriceRange(Builder $query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeOutOfStock(Builder $query)
    {
        return $query->where('quantity_available', '<=', 0);
    }

    public function scopePublished(Builder $query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    // Accessors
    public function getDiscountedPriceAttribute(): float
    {
        return $this->price * (1 - $this->discount_percentage / 100);
    }

    public function getIsFavoriteAttribute(): bool
    {
        return $this->favorite_count > 0;
    }

    public function getEffectiveOrderStepAttribute(): int
    {
        if (!$this->is_bulk_order_enabled) {
            return 1;
        }

        return max(1, (int) ($this->order_quantity_step ?: 1));
    }

    public function getNormalizedOrderQuantityOptionsAttribute(): array
    {
        $options = is_array($this->order_quantity_options) ? $this->order_quantity_options : [];

        $normalized = collect($options)
            ->map(fn($value) => (int) $value)
            ->filter(fn($value) => $value > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $normalized;
    }

    public function validateOrderQuantity(int $quantity): ?string
    {
        if ($quantity < 1) {
            return 'Quantity must be at least 1.';
        }

        // If explicit choices are configured, treat them as allowed pack sizes.
        // Final quantity can be any positive multiple of at least one configured pack size.
        $optionList = $this->normalized_order_quantity_options;
        if (!empty($optionList)) {
            $isValidMultiple = collect($optionList)->contains(function ($option) use ($quantity) {
                $option = (int) $option;
                return $option > 0 && $quantity % $option === 0;
            });

            if (!$isValidMultiple) {
                return "{$this->name} quantity must be a multiple of one of these choices: " . implode(', ', $optionList) . " {$this->unit}.";
            }

            return null;
        }

        $minimumOrder = max(1, (int) ($this->minimum_order ?: 1));
        if ($quantity < $minimumOrder) {
            return "Minimum order for {$this->name} is {$minimumOrder} {$this->unit}.";
        }

        if ($this->is_bulk_order_enabled) {
            $step = $this->effective_order_step;
            if ($quantity % $step !== 0) {
                return "{$this->name} must be ordered in multiples of {$step} {$this->unit}.";
            }
        }

        return null;
    }
}
