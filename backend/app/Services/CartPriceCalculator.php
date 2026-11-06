<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use LogicException;

class CartPriceCalculator
{
    public function unitPrice(ProductVariant $variant): string
    {
        $product = $variant->product;

        if (! $product instanceof Product) {
            throw new LogicException('Product variant is missing its product relation.');
        }

        return $this->quote($product, $variant)['effective_price'];
    }

    /**
     * @return array{original_price: string, effective_price: string, is_promotion: bool, discount_percentage: int}
     */
    public function quote(Product $product, ?ProductVariant $variant = null): array
    {
        $originalPrice = $variant?->price ?? $product->price;
        $originalMinor = $this->toMinorUnits($originalPrice);

        if ($originalMinor < 0) {
            throw new LogicException('Stored product price cannot be negative.');
        }

        $salePrice = $variant?->price !== null
            ? $variant->sale_price
            : $product->sale_price;
        $saleMinor = $salePrice === null ? null : $this->toMinorUnits($salePrice);
        $saleMustBePositive = $variant?->price !== null;
        $hasPromotion = $saleMinor !== null
            && ($saleMustBePositive ? $saleMinor > 0 : $saleMinor >= 0)
            && $saleMinor < $originalMinor;
        $effectiveMinor = $hasPromotion ? $saleMinor : $originalMinor;

        return [
            'original_price' => $this->formatMinorUnits($originalMinor),
            'effective_price' => $this->formatMinorUnits($effectiveMinor),
            'is_promotion' => $hasPromotion,
            'discount_percentage' => $hasPromotion && $originalMinor > 0
                ? (int) round((($originalMinor - $effectiveMinor) * 100) / $originalMinor)
                : 0,
        ];
    }

    public function isPurchasable(ProductVariant $variant): bool
    {
        $product = $variant->product;

        return $variant->status === 'active'
            && $product instanceof Product
            && ! $product->trashed()
            && $product->status === 'active'
            && $product->category?->status === 'active'
            && ($product->brand === null || $product->brand->status === 'active');
    }

    public function toMinorUnits(string|int|float $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new LogicException('Stored monetary value does not match DECIMAL(12,2).');
        }

        $fraction = str_pad($matches[3] ?? '', 2, '0');
        $minorUnits = ((int) $matches[2] * 100) + (int) $fraction;

        return $matches[1] === '-' ? -$minorUnits : $minorUnits;
    }

    public function multiplyMinorUnits(int $amount, int $quantity): string
    {
        $left = array_map('intval', str_split(strrev((string) $amount)));
        $right = array_map('intval', str_split(strrev((string) $quantity)));
        $result = array_fill(0, count($left) + count($right), 0);

        foreach ($left as $leftIndex => $leftDigit) {
            $carry = 0;

            foreach ($right as $rightIndex => $rightDigit) {
                $index = $leftIndex + $rightIndex;
                $value = $result[$index] + ($leftDigit * $rightDigit) + $carry;
                $result[$index] = $value % 10;
                $carry = intdiv($value, 10);
            }

            $index = $leftIndex + count($right);

            while ($carry > 0) {
                $value = $result[$index] + $carry;
                $result[$index] = $value % 10;
                $carry = intdiv($value, 10);
                $index++;
            }
        }

        return ltrim(strrev(implode('', $result)), '0') ?: '0';
    }

    public function addMinorUnitAmounts(string $left, string $right): string
    {
        $leftDigits = str_split(strrev($left));
        $rightDigits = str_split(strrev($right));
        $length = max(count($leftDigits), count($rightDigits));
        $result = '';
        $carry = 0;

        for ($index = 0; $index < $length; $index++) {
            $sum = (int) ($leftDigits[$index] ?? 0)
                + (int) ($rightDigits[$index] ?? 0)
                + $carry;
            $result .= (string) ($sum % 10);
            $carry = intdiv($sum, 10);
        }

        if ($carry > 0) {
            $result .= (string) $carry;
        }

        return strrev($result);
    }

    public function formatMinorUnits(string|int $amount): string
    {
        $digits = str_pad((string) $amount, 3, '0', STR_PAD_LEFT);
        $whole = ltrim(substr($digits, 0, -2), '0') ?: '0';

        return $whole.'.'.substr($digits, -2);
    }
}
