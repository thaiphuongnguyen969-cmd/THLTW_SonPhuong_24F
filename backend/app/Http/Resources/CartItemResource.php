<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use App\Services\CartPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variant = $this->productVariant;

        if (! $variant instanceof ProductVariant) {
            throw new LogicException('Cart item is missing its product variant relation.');
        }

        /** @var CartPriceCalculator $prices */
        $prices = app(CartPriceCalculator::class);
        $unitPrice = $prices->unitPrice($variant);
        $product = $variant->product;

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
            ],
            'variant' => [
                'id' => $variant->id,
                'size' => [
                    'id' => $variant->size->id,
                    'name' => $variant->size->name,
                ],
                'color' => [
                    'id' => $variant->color->id,
                    'name' => $variant->color->name,
                    'hex_code' => $variant->color->hex_code,
                ],
            ],
            'unit_price' => $unitPrice,
            'subtotal' => $prices->formatMinorUnits(
                $prices->multiplyMinorUnits(
                    $prices->toMinorUnits($unitPrice),
                    (int) $this->quantity
                )
            ),
            'available' => $prices->isPurchasable($variant)
                && (int) $variant->stock >= (int) $this->quantity,
        ];
    }
}
