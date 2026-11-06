<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        /** @var ProductVariant|null $variant */
        $variant = $this->route('variant');

        if ($product && $variant && (int) $variant->product_id !== (int) $product->getKey()) {
            abort(404);
        }

        return true;
    }

    public function rules(): array
    {
        /** @var ProductVariant|null $variant */
        $variant = $this->route('variant');
        $put = $this->isMethod('put');
        $patch = $this->isMethod('patch');

        return [
            'sku' => [
                $patch ? 'sometimes' : 'required',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variant?->getKey()),
            ],
            'size_id' => [
                $patch ? 'sometimes' : 'required',
                'integer',
                'exists:sizes,id',
            ],
            'color_id' => [
                $patch ? 'sometimes' : 'required',
                'integer',
                'exists:colors,id',
            ],
            'price' => [
                $patch || ! $put ? 'sometimes' : 'present',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999.99',
            ],
            'sale_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:9999999999.99',
            ],
            'stock' => [$patch || ! $put ? 'sometimes' : 'required', 'integer', 'min:0', 'max:4294967295'],
            'expected_stock' => [
                $variant ? 'sometimes' : 'prohibited',
                'integer',
                'min:0',
                'max:4294967295',
            ],
            'status' => [$patch || ! $put ? 'sometimes' : 'required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowedFields = ['sku', 'size_id', 'color_id', 'price', 'sale_price', 'stock', 'expected_stock', 'status'];

                foreach (array_diff(array_keys($this->all()), $allowedFields) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép gửi.');
                }

                /** @var ProductVariant|null $variant */
                $variant = $this->route('variant');

                if ($variant && $this->exists('stock') && ! $this->exists('expected_stock')) {
                    $validator->errors()->add(
                        'expected_stock',
                        'Cần gửi tồn kho hiện tại mà bạn kỳ vọng trước khi cập nhật stock.'
                    );
                }

                if ($variant && ! $this->exists('stock') && $this->exists('expected_stock')) {
                    $validator->errors()->add(
                        'expected_stock',
                        'expected_stock chỉ được gửi khi cập nhật stock.'
                    );
                }

                if ($this->isMethod('patch') && $this->all() === []) {
                    $validator->errors()->add('variant', 'PATCH phải gửi ít nhất một trường được phép cập nhật.');
                }

                if (! $validator->errors()->has('price') && ! $validator->errors()->has('sale_price')) {
                    /** @var ProductVariant|null $variant */
                    $variant = $this->route('variant');
                    $price = $this->exists('price') ? $this->input('price') : $variant?->price;
                    $salePrice = $this->exists('sale_price') ? $this->input('sale_price') : $variant?->sale_price;

                    if ($salePrice !== null && $price === null) {
                        $validator->errors()->add('sale_price', 'Cần có giá gốc trước khi đặt giá khuyến mãi.');
                    } elseif ($salePrice !== null && (float) $salePrice >= (float) $price) {
                        $validator->errors()->add('sale_price', 'Giá khuyến mãi phải thấp hơn giá gốc của biến thể.');
                    }
                }

                if (
                    $validator->errors()->has('size_id')
                    || $validator->errors()->has('color_id')
                ) {
                    return;
                }

                /** @var Product $product */
                $product = $this->route('product');
                $sizeId = $this->input('size_id', $variant?->size_id);
                $colorId = $this->input('color_id', $variant?->color_id);

                $duplicate = ProductVariant::query()
                    ->where('product_id', $product->getKey())
                    ->where('size_id', $sizeId)
                    ->where('color_id', $colorId)
                    ->when($variant, fn ($query) => $query->where('id', '<>', $variant->getKey()))
                    ->exists();

                if ($duplicate) {
                    $field = $this->exists('color_id') && ! $this->exists('size_id')
                        ? 'color_id'
                        : 'size_id';

                    $validator->errors()->add($field, 'Tổ hợp kích thước và màu sắc này đã tồn tại cho sản phẩm.');
                }
            },
        ];
    }
}
