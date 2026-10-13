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

        if ($variant->price !== null) {
            if ($this->toMinorUnits($variant->price) < 0) {
                throw new LogicException('Stored variant price cannot be negative.');
            }

            return (string) $variant->price;
        }

        if ($product->sale_price !== null) {
            $salePrice = $this->toMinorUnits($product->sale_price);

            if ($salePrice >= 0 && $salePrice < $this->toMinorUnits($product->price)) {
                return (string) $product->sale_price;
            }
        }

        return (string) $product->price;
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
