<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['farmOwner:id,farm_name,permit_status', 'farmOwner.user:id,name'])
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->whereHas('farmOwner', function ($query) {
                $query->where('permit_status', 'approved');
            });

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        $paginator = $query
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        $products = collect($paginator->items())->map(function (Product $product) {
                $imageUrl = (string) ($product->image_url ?? '');

                if ($imageUrl !== '' && !str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
                    $imageUrl = url($imageUrl);
                }

                return [
                    'id' => $product->id,
                    'farm_owner_id' => $product->farm_owner_id,
                    'farm_name' => $product->farmOwner?->farm_name ?? 'Unknown Farm',
                    'farm_owner_name' => $product->farmOwner?->user?->name ?? 'Farm Owner',
                    'shop_rating' => (float) ($product->farmOwner?->average_rating ?? 0),
                    'shop_type' => in_array($product->category, ['feeds', 'equipment', 'other'], true)
                        ? 'Supply Shop'
                        : 'Poultry Shop',
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->quantity_available,
                    'unit' => (string) $product->unit,
                    'minimum_order' => (int) $product->minimum_order,
                    'is_bulk_order_enabled' => (bool) $product->is_bulk_order_enabled,
                    'order_quantity_step' => (int) ($product->order_quantity_step ?: 1),
                    'order_quantity_options' => $product->normalized_order_quantity_options,
                    'image_url' => $imageUrl,
                    'category' => $product->category,
                ];
            })->values();

        return response()->json([
            'data' => $products,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
