<?php

namespace App\Http\Requests;

use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:50', Rule::in(OrderService::adminStatuses())],
            'expected_status' => ['required', 'string', 'max:50'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), ['status', 'expected_status', 'reason']) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép gửi.');
                }
            },
        ];
    }
}
