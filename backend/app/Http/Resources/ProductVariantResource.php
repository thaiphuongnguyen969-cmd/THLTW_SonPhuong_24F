<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\CartPriceCalculator;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CartPriceCalculator $prices */
        $prices = app(CartPriceCalculator::class);
        $quote = $prices->quote($this->product, $this->resource);
        $variant = [
            'id' => $this->id,
            'size_id' => $this->size_id,
            'color_id' => $this->color_id,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            ...$quote,
            'size' => $this->whenLoaded('size', fn () => [
                'id' => $this->size->id,
                'name' => $this->size->name,
            ]),
            'color' => $this->whenLoaded('color', fn () => [
                'id' => $this->color->id,
                'name' => $this->color->name,
                'hex_code' => $this->color->hex_code,
            ]),
        ];

        if ($request->user()?->role === 'admin' && $request->user()?->status === 'active') {
            return [
                ...$variant,
                'sku' => $this->sku,
                'stock' => $this->stock,
                'status' => $this->status,
            ];
        }

        return [
            ...$variant,
            'available' => $this->stock > 0,
        ];
    }
}
