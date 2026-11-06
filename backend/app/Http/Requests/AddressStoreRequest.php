<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddressStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ward' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_detail' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowedFields = [
                    'label',
                    'recipient_name',
                    'phone',
                    'province',
                    'district',
                    'ward',
                    'address_detail',
                ];

                foreach (array_diff(array_keys($this->all()), $allowedFields) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép gửi.');
                }
            },
        ];
    }
}
