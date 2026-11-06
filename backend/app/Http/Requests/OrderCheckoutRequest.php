<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OrderCheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer'],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), ['address_id', 'note']) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép gửi.');
                }
            },
        ];
    }
}
