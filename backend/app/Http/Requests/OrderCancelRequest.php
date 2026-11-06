<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OrderCancelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), ['reason']) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép gửi.');
                }
            },
        ];
    }
}
