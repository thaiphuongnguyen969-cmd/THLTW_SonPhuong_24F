<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:200'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'integer', 'exists:brands,id'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'all'])],
            'sort_by' => ['sometimes', 'string', Rule::in(['name', 'price', 'created_at'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $this->filled('min_price')
                    && $this->filled('max_price')
                    && (float) $this->input('min_price') > (float) $this->input('max_price')
                ) {
                    $validator->errors()->add('max_price', 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.');
                }
            },
        ];
    }
}
