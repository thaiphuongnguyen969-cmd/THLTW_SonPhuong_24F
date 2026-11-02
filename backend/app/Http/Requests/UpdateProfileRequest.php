<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $emailRules = ['sometimes', 'required', 'string', 'email', 'max:100'];

        if ($userId = $this->user()?->getAuthIdentifier()) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($userId);
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => $emailRules,
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowedFields = ['name', 'email', 'phone', 'address'];

                foreach (array_diff(array_keys($this->all()), $allowedFields) as $field) {
                    $validator->errors()->add($field, 'Trường này không được phép cập nhật.');
                }

                if ($this->all() === []) {
                    $validator->errors()->add('profile', 'Vui lòng gửi ít nhất một trường cần cập nhật.');
                }
            },
        ];
    }
}
