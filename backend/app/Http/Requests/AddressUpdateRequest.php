<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddressUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $put = $this->isMethod('put');

        return [
            'label' => [$put ? 'required' : 'sometimes', 'string', 'max:100'],
            'recipient_name' => [$put ? 'required' : 'sometimes', 'string', 'max:100'],
            'phone' => [$put ? 'present' : 'sometimes', 'nullable', 'string', 'max:20'],
            'province' => [$put ? 'present' : 'sometimes', 'nullable', 'string', 'max:100'],
            'district' => [$put ? 'present' : 'sometimes', 'nullable', 'string', 'max:100'],
            'ward' => [$put ? 'present' : 'sometimes', 'nullable', 'string', 'max:100'],
            'address_detail' => [$put ? 'required' : 'sometimes', 'string'],
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

                if ($this->isMethod('patch') && $this->all() === []) {
                    $validator->errors()->add('address', 'PATCH phải gửi ít nhất một trường được phép cập nhật.');
                }
            },
        ];
    }
}
