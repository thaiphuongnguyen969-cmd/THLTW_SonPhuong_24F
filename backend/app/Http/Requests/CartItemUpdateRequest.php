<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CartItemUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), ['quantity']) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép gửi.');
                }
            },
        ];
    }
}
