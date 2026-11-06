<?php

namespace App\Http\Resources;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CartPriceCalculator $prices */
        $prices = app(CartPriceCalculator::class);
        $items = $this->items;
        $totalQuantity = 0;
        $totalMinorUnits = '0';

        foreach ($items as $item) {
            if (! $item instanceof CartItem || ! $item->productVariant instanceof ProductVariant) {
                throw new LogicException('Cart item is missing its product variant relation.');
            }

            $totalQuantity += (int) $item->quantity;
            $itemMinorUnits = $prices->multiplyMinorUnits(
                $prices->toMinorUnits($prices->unitPrice($item->productVariant)),
                (int) $item->quantity
            );
            $totalMinorUnits = $prices->addMinorUnitAmounts($totalMinorUnits, $itemMinorUnits);
        }

        return [
            'items' => CartItemResource::collection($items),
            'total_quantity' => $totalQuantity,
            'total' => $prices->formatMinorUnits($totalMinorUnits),
        ];
    }
}
