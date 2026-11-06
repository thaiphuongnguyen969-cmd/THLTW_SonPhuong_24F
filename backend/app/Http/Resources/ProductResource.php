<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\CartPriceCalculator;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : collect();
        $variants->each(fn ($variant) => $variant->setRelation('product', $this->resource));
        /** @var CartPriceCalculator $prices */
        $prices = app(CartPriceCalculator::class);
        $purchasableVariants = $variants->where('status', 'active')->filter(fn ($variant) => (int) $variant->stock > 0);
        $quotes = $purchasableVariants->map(fn ($variant) => $prices->quote($this->resource, $variant));
        $effectivePrices = $quotes->pluck('effective_price');
        $lowestQuote = $quotes->sort(fn ($left, $right) => (float) $left['effective_price'] <=> (float) $right['effective_price'])->first();
        $uniquePricePairs = $quotes->map(fn ($quote) => $quote['original_price'].'|'.$quote['effective_price'])->unique();
        $singlePricePair = $uniquePricePairs->count() === 1 ? $quotes->first() : null;
        $singlePairIsPromotion = (bool) ($singlePricePair['is_promotion'] ?? false);
        $hasPromotion = $quotes->contains(fn ($quote) => $quote['is_promotion']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'original_price' => $this->when($this->resource->relationLoaded('variants'), $singlePricePair['original_price'] ?? null),
            'effective_price' => $this->when($this->resource->relationLoaded('variants'), $lowestQuote['effective_price'] ?? null),
            'price_from' => $this->when($this->resource->relationLoaded('variants'), $effectivePrices->unique()->count() > 1),
            'promotion_price' => $this->when($this->resource->relationLoaded('variants'), $singlePairIsPromotion ? $singlePricePair['effective_price'] : null),
            'is_promotion' => $this->when($this->resource->relationLoaded('variants'), $hasPromotion),
            'discount_percentage' => $this->when($this->resource->relationLoaded('variants'), $singlePairIsPromotion ? $singlePricePair['discount_percentage'] : null),
            'has_promotion' => $this->when($this->resource->relationLoaded('variants'), $hasPromotion),
            'total_sold' => $this->when(isset($this->total_sold), (int) $this->total_sold),
            'status' => $this->status,
            'image' => $this->image,
            'quantity' => $this->when(
                $request->user()?->role === 'admin' && $request->user()?->status === 'active',
                fn () => $this->quantity
            ),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo' => $this->brand->logo,
            ] : null),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => $this->when(
                ! $this->resource->getAttribute('_hide_variants_resource') && $this->resource->relationLoaded('variants'),
                fn () => ProductVariantResource::collection($this->variants)
            ),
            'promotions' => $this->whenLoaded('promotions', fn () => $this->promotions->map(fn ($promotion) => [
                'id' => $promotion->id,
                'code' => $promotion->code,
                'name' => $promotion->name,
                'discount_type' => $promotion->discount_type,
                'discount_value' => $promotion->discount_value,
                'starts_at' => $promotion->starts_at?->toDateTimeString(),
                'ends_at' => $promotion->ends_at?->toDateTimeString(),
            ])),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
