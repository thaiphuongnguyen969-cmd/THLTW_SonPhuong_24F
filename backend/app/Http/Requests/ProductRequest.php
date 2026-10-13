<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post') && ! $this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $updating = $this->isMethod('put') || $this->isMethod('patch');
        $slugUnique = Rule::unique('products', 'slug');

        if ($product) {
            $slugUnique->ignore($product->getKey());
        }

        return [
            'category_id' => [
                $updating ? 'sometimes' : 'required',
                'integer',
                'exists:categories,id',
            ],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:200'],
            'slug' => [
                $updating ? 'sometimes' : 'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slugUnique,
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => [
                $updating ? 'sometimes' : 'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:0', 'max:2147483647'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'images' => ['prohibited'],
            'variants' => ['prohibited'],
            'sku' => ['prohibited'],
            'stock' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $validator->errors()->has('sale_price')
                    || $validator->errors()->has('price')
                ) {
                    return;
                }

                /** @var Product|null $product */
                $product = $this->route('product');
                $price = $this->input('price', $product?->price);
                $salePrice = $this->input('sale_price', $product?->sale_price);

                if ($price !== null && $salePrice !== null && (float) $salePrice >= (float) $price) {
                    $field = $this->exists('sale_price') ? 'sale_price' : 'price';
                    $validator->errors()->add($field, 'Giá khuyến mãi phải nhỏ hơn giá gốc.');
                }
            },
        ];
    }
}
